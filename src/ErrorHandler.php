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
use Whoops\{Run, RunInterface};
use Throwable;

use function error_reporting;
use function set_error_handler;
use function restore_error_handler;
use function ob_start;
use function ob_get_clean;

class ErrorHandler implements MiddlewareInterface
{
    /** @var int */
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

    private ResponseFactoryInterface $responseFactory;

    private RunInterface $whoops;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->whoops = new Run();
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $format = FormatNegotiator::fromRequest($request);
        $this->whoops->pushHandler($format->getHandler());

        set_error_handler($this->createErrorHandler());

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $response = $this->handleError($e)
                ->withHeader('Content-Type', $format->getPreferredContentType());
        }

        restore_error_handler();

        return $response;
    }

    private function handleError(Throwable $exception): ResponseInterface
    {
        $this->whoops->allowQuit(false);
        $method = Run::EXCEPTION_HANDLER;
        $code = http_response_code();

        ob_start();
        $this->whoops->$method($exception);
        $response = $this->responseFactory->createResponse(
            ($code < 400 || $code > 600) ? self::STATUS_INTERNAL_SERVER_ERROR : $code
        );
        $response->getBody()->write(ob_get_clean());

        return $response;
    }

    private function createErrorHandler(): callable
    {
        $errorHandlerMethod = Run::ERROR_HANDLER;

        return function (
            int $errno,
            string $errstr,
            string $errfile,
            int $errline
        ) use ($errorHandlerMethod) {
            if (! (error_reporting() & $errno)) {
                return;
            }

            $this->whoops->{$errorHandlerMethod}($errno, $errstr, $errfile, $errline);
        };
    }
}
