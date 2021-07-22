<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2021 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops;

use Psr\Http\Message\{ResponseFactoryInterface, ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use Whoops\{Exception\ErrorException, Exception\Inspector, Handler\Handler, Run, RunInterface};
use Whoops\Util\{SystemFacade, Misc};
use BitFrame\Whoops\Provider\{HandlerProviderNegotiator, ProviderInterface};
use Throwable;
use InvalidArgumentException;

use function is_a;
use function array_reverse;
use function in_array;
use function method_exists;
use function http_response_code;

class ErrorHandler implements MiddlewareInterface
{
    use HandlerOptionsAwareTrait;

    /** @var int */
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

    private SystemFacade $system;

    private RunInterface $whoops;

    private ResponseFactoryInterface $responseFactory;

    /** @var ProviderInterface|string */
    private $handlerProvider;

    private array $options;

    private bool $catchGlobalErrors;

    private bool $canThrowExceptions = true;

    public static function fromNegotiator(
        ResponseFactoryInterface $responseFactory,
        array $options = []
    ): self {
        return new self(
            $responseFactory,
            HandlerProviderNegotiator::class,
            $options
        );
    }

    /**
     * @param ResponseFactoryInterface $responseFactory
     * @param string|ProviderInterface $handlerProvider
     * @param array $options
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        $handlerProvider = HandlerProviderNegotiator::class,
        array $options = []
    ) {
        $this->responseFactory = $responseFactory;
        $this->handlerProvider = $handlerProvider;

        if (! is_a($this->handlerProvider, ProviderInterface::class, true)) {
            throw new InvalidArgumentException(
                'Handler provider must be instance of ' . ProviderInterface::class
            );
        }

        $this->options = $options;
        $this->catchGlobalErrors = $options['catchGlobalErrors'] ?? false;
        unset($options['catchGlobalErrors']);

        $this->system = new SystemFacade();
        $this->whoops = new Run($this->system);
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->whoops->allowQuit(false);
        $this->whoops->writeToOutput($this->catchGlobalErrors);

        $this->system->setErrorHandler([$this, 'handleError']);
        $this->system->setExceptionHandler([$this, 'handleException']);
        $this->system->registerShutdownFunction([$this, 'handleShutdown']);

        $handlerProvider = ($this->handlerProvider instanceof ProviderInterface)
            ? $this->handlerProvider
            : new $this->handlerProvider();
        $errorHandler = $handlerProvider->getHandler($request);

        $this->applyOptions($errorHandler);
        $this->whoops->pushHandler($errorHandler);

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $output = $this->handleException($e);

            $response = $this->responseFactory->createResponse($this->getStatusCode());
            $response->getBody()->write($output);
        }

        if (! $this->catchGlobalErrors) {
            $this->system->restoreErrorHandler();
            $this->system->restoreExceptionHandler();
        }

        return $response;
    }

    public function handleException(Throwable $exception): string
    {
        $inspector = new Inspector($exception);

        $this->system->startOutputBuffering();

        $handlerResponse = null;
        $handlerContentType = null;
        $handlerStack = array_reverse($this->whoops->getHandlers());

        try {
            foreach ($handlerStack as $handler) {
                $handler->setRun($this->whoops);
                $handler->setInspector($inspector);
                $handler->setException($exception);

                $handlerResponse = $handler->handle();

                $handlerContentType = method_exists($handler, 'contentType')
                    ? $handler->contentType()
                    : null;

                if (in_array($handlerResponse, [Handler::LAST_HANDLER, Handler::QUIT])) {
                    break;
                }
            }
        } finally {
            $output = $this->system->cleanOutputBuffer();
        }

        if ($this->whoops->writeToOutput()) {
            if (Misc::canSendHeaders() && $handlerContentType) {
                header("Content-Type: {$handlerContentType}", true, $this->getStatusCode());
            }

            $this->writeToOutputNow($output);
        }

        return $output;
    }

    /**
     * @param int $level
     * @param string $message
     * @param null|string $file
     * @param null|int $line
     *
     * @return bool
     * @throws ErrorException
     */
    public function handleError(
        int $level,
        string $message,
        ?string $file = null,
        ?int $line = null
    ): bool {
        if ($level & $this->system->getErrorReportingLevel()) {
            // XXX we pass `$level` for the "code" param only for BC reasons.
            // @see https://github.com/filp/whoops/issues/267
            $exception = new ErrorException($message, /*code*/ $level, /*severity*/ $level, $file, $line);
            if ($this->canThrowExceptions) {
                throw $exception;
            }

            $this->handleException($exception);
            return true;
        }

        return false;
    }

    /**
     * @throws ErrorException
     */
    public function handleShutdown()
    {
        $this->canThrowExceptions = false;

        $error = $this->system->getLastError();
        if ($error && Misc::isLevelFatal($error['type'])) {
            $this->handleError($error['type'], $error['message'], $error['file'], $error['line']);
        }
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function writeToOutputNow(string $output): self
    {
        $statusCode = $this->getStatusCode();

        if ($this->whoops->sendHttpCode($statusCode) && Misc::canSendHeaders()) {
            $this->system->setHttpResponseCode(
                $this->whoops->sendHttpCode($statusCode)
            );
        }

        echo $output;

        return $this;
    }

    private function getStatusCode(): int
    {
        $statusCode = http_response_code();

        if ($statusCode < 400 || $statusCode > 600) {
            $statusCode = self::STATUS_INTERNAL_SERVER_ERROR;
        }
        return $statusCode;
    }
}
