<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops;

use Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use Whoops\Run;
use Whoops\Util\Misc;
use Whoops\Handler\{
    PlainTextHandler,
    JsonResponseHandler,
    XmlResponseHandler,
    PrettyPageHandler
};
use BitFrame\Delegate\CallableMiddlewareTrait;
use BitFrame\Whoops\Handler\JsonpResponseHandler;

/**
 * Whoops error handler middleware to handle application
 * or middleware specific errors.
 */
class WhoopsErrorHandler implements MiddlewareInterface
{
    use CallableMiddlewareTrait;
    
    /** @var callable|string */
    private $outputFormat;
    
    /** @var bool */
    private $handleGlobalErrors;
    
    /** @var bool */
    private $showTrace;
    
    /**
     * @param callable|string $outputFormat (optional)
     * @param bool $handleGlobalErrors (optional)
     * @param bool $showTrace (optional)
     */
    public function __construct(
        $outputFormat = 'auto', 
        bool $handleGlobalErrors = false, 
        bool $showTrace = false
    )
    {
        $this->outputFormat = $outputFormat;
        $this->handleGlobalErrors = $handleGlobalErrors;
        $this->showTrace = $showTrace;
    }
    
    /**
     * {@inheritdoc}
     *
     * @throws \TypeError
     * @throws \UnexpectedValueException
     */
    public function process(
        ServerRequestInterface $request, 
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        // unregister all previously registered handlers
        $whoops = (self::getWhoopsInstance($request, $this->outputFormat, $this->showTrace));
        $whoops->popHandler();
        $whoops->unregister();
        
        // handle all PHP errors globally?
        if ($this->handleGlobalErrors) {
            // register whoops handler
            $this->handleThrowable(null, $request);
            
            // handle all subsequent requests, if any
            return $handler->handle($request);
        }
        
        // check if there are other middlewares in the chain that need to be processed
        // (i.e. the error handler was setup as a part of the current chain); in such
        // instance handle errors specifically within the middleware chain
        set_error_handler($this->createErrorHandler($request));

        try {
            if (! $request instanceof ServerRequestInterface) {
                throw new \TypeError(sprintf(
                    'Expecting a "%s" instance; instance of "%s" provided',
                    ServerRequestInterface::class,
                    get_class($request)
                ));
            }

            // continue processing all requests
            $response = $handler->handle($request);

            // invalid response received?
            if (! ($response instanceof ResponseInterface)) {
                throw new \UnexpectedValueException(sprintf(
                    'Application must return a valid "%s" instance',
                    ResponseInterface::class
                ));
            }

            // catch errors if there are any
        } catch (\Throwable | \Exception $e) {
            $response = $this->handleThrowable($e, $request);
        }

        restore_error_handler();
        
        return $response;
    }
    
    /**
     * Get Whoops object instance.
     *
     * @param ServerRequestInterface $request
     * @param string|callable $format (optional)
     * @param bool $showTrace (optional)
     *
     * @return \Whoops\Run
     */
    public static function getWhoopsInstance(
        ServerRequestInterface $request, 
        $format = 'auto', 
        bool $showTrace = false
    ): Run
    {
        $whoops = new Run();
        
        // custom handler specified?
        if (is_callable($format)) {
            $handler = $format;
        } else {
            // select appropriate handler as per the requested format
            $format = ((PHP_SAPI === 'cli')) ? 'text' : (
                ($format === 'auto') ? (
                    (Misc::isAjaxRequest()) ? 'json' : FormatNegotiator::getPreferredFormat($request)
                ) : $format
            );

            switch ($format) {
                case 'json':
                    $handler = new JsonResponseHandler;
                    
                    // is a jsonp request?
                    if (
                        $request->getMethod() === 'GET' && 
                        ! empty($callback = $request->getQueryParam('callback', ''))
                    ) {
                        // use the custom jsonp response handler
                        $handler = new JsonpResponseHandler($callback);
                    }
                    
                    $handler->addTraceToOutput($showTrace);
                    $handler->setJsonApi(true);
                break;
                case 'html':
                    $handler = new PrettyPageHandler;
                break;
                case 'txt':
                case 'text':
                case 'plain':
                    $handler = new PlainTextHandler;
                    $handler->addTraceToOutput($showTrace);
                break;
                case 'xml':
                    $handler = new XmlResponseHandler;
                    $handler->addTraceToOutput($showTrace);
                break;
                default:
                    if (empty($format)) {
                        $handler = new PrettyPageHandler;
                    } else {
                        $handler = new PlainTextHandler;
                        $handler->addTraceToOutput($showTrace);
                    }
                break;
            }
            
            // extra attributes to add to whoops pretty page handler...
            if ($handler instanceof PrettyPageHandler) {
                $handler->addDataTable('Application/Request Data', [
                    'HTTP Method'            => $request->getMethod(),
                    'URI'                    => (string) $request->getUri(),
                    'Script'                 => $request->getServerParams()['SCRIPT_NAME'],
                    'Headers'                => $request->getHeaders(),
                    'Cookies'                => $request->getCookieParams(),
                    'Attributes'             => $request->getAttributes(),
                    'Query String Arguments' => $request->getQueryParams(),
                    'Body Params'            => $request->getParsedBody(),
                ]);
            }
        }
        
        // register handler
        $whoops->pushHandler($handler);
        
        return $whoops;
    }
    
    /**
     * Handle caught exceptions/throwables.
     *
     * @param Exception|Throwable $error
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    private function handleThrowable(
        $error, 
        ServerRequestInterface $request
    ): ResponseInterface
    {
        $whoops = self::getWhoopsInstance($request, $this->outputFormat, $this->showTrace);
        
        // note: php errors handled by Whoops are converted to ErrorException, but getCode() returns 
        // the same value as getSeverity() (e.g. E_WARNING would return 2) so we fix this by setting
        // ErrorExceptions to code 500
        // @see https://github.com/filp/whoops/issues/267
        $initialCode = ($error === null || $error instanceof \ErrorException || ($code = $error->getCode()) < 100 || $code >= 600) ? 500 : $code;
        
        // output is managed by the middleware pipeline
        $whoops->allowQuit((PHP_SAPI !== 'cli'));
        $whoops->writeToOutput(true);
        $whoops->sendHttpCode($initialCode);
        
        // register an internal whoops instance as an error/exception/shutdown handler
        $whoopsInternalHandler = self::getWhoopsInstance($request, function($exception, $inspector, $error_handler) use ($whoops, $initialCode) {
            // get exception code
            $code = $exception->getCode();

            $whoops->sendHttpCode(($initialCode !== $code && $code >= 100 && $code < 600 && ($initialCode === 500 || $initialCode === 200 || $initialCode < 100 || $initialCode >= 600)) ? $code : $initialCode);

            $whoops->{Run::SHUTDOWN_HANDLER}();
            // handling specific errors within the error_reporting mask?
            // delegate to whoops error handler method and output immediately
            $whoops->{Run::EXCEPTION_HANDLER}($exception);

            return \Whoops\Handler\Handler::QUIT;
        }, $this->showTrace);

        $whoopsInternalHandler->register();
        
        return \BitFrame\Factory\HttpMessageFactory::createResponse($initialCode);
    }
    
    /**
     * Creates and returns a callable error handler that raises exceptions.
     *
     * Only raises exceptions for errors that are within the error_reporting mask.
     *
     * @return callable
     */
    private function createErrorHandler(ServerRequestInterface $request): callable
    {
        $whoops = self::getWhoopsInstance($request, $this->outputFormat, $this->showTrace);
        $errorHandlerMethod = Run::ERROR_HANDLER;
        
        /**
         * @param int $errno
         * @param string $errstr
         * @param string $errfile
         * @param int $errline
         *
         * @return void
         *
         * @throws \ErrorException if error is not within the error_reporting mask.
         */
        return function ($errno, $errstr, $errfile, $errline) use ($whoops, $errorHandlerMethod) 
        {
            if (! (error_reporting() & $errno)) {
                // error_reporting does not include this error
                return;
            }
            
            $whoops->{$errorHandlerMethod}($errno, $errstr, $errfile, $errline);
        };
    }
}
