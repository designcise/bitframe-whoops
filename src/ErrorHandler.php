<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops;

use Psr\Http\Message\{ResponseFactoryInterface, ServerRequestInterface, ResponseInterface};
use Psr\Http\Server\{RequestHandlerInterface, MiddlewareInterface};
use Whoops\Run;
use Throwable;
use Whoops\RunInterface;

use function error_reporting;
use function set_error_handler;
use function restore_error_handler;
use function ob_start;
use function ob_get_clean;

class ErrorHandler implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $format = FormatNegotiator::fromRequest($request);
        $whoops = new Run();
        $whoops->pushHandler($format->getHandler());

        set_error_handler($this->createErrorHandler($whoops));

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $response = $this->handleError($whoops, $e)
                ->withHeader('Content-Type', $format->getPreferredContentType());
        }

        restore_error_handler();

        return $response;
    }

    private function handleError(
        RunInterface $whoops,
        Throwable $exception
    ): ResponseInterface {
        $method = Run::EXCEPTION_HANDLER;

        $whoops->allowQuit(false);

        ob_start();
        $whoops->$method($exception);
        $response = $this->responseFactory->createResponse(500);
        $response->getBody()->write(ob_get_clean());

        return $response;
    }

    private function createErrorHandler(RunInterface $whoops): callable
    {
        $errorHandlerMethod = Run::ERROR_HANDLER;

        return static function (
            int $errno,
            string $errstr,
            string $errfile,
            int $errline
        ) use ($whoops, $errorHandlerMethod) {
            if (! (error_reporting() & $errno)) {
                return;
            }

            $whoops->{$errorHandlerMethod}($errno, $errstr, $errfile, $errline);
        };
    }
}
