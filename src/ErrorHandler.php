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

use function set_error_handler;
use function restore_error_handler;
use function ob_start;
use function ob_get_clean;
use function method_exists;

class ErrorHandler implements MiddlewareInterface
{
    use HandlerOptionsAwareTrait;

    /** @var int */
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

    private ResponseFactoryInterface $responseFactory;

    private RunInterface $whoops;

    private array $options;

    public function __construct(ResponseFactoryInterface $responseFactory, array $options = [])
    {
        $this->responseFactory = $responseFactory;
        $this->options = $options;
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
        $errorHandler = $format->getHandler();
        $this->applyOptions($errorHandler);
        $this->whoops->pushHandler($errorHandler);

        $this->whoops->allowQuit(false);
        $this->whoops->writeToOutput(true);

        set_error_handler([$this->whoops, Run::ERROR_HANDLER]);
        register_shutdown_function([$this->whoops, Run::SHUTDOWN_HANDLER]);

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $response = $this->handleException($e)
                ->withHeader('Content-Type', $format->getPreferredContentType());
        }

        restore_error_handler();

        return $response;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function handleException(Throwable $exception): ResponseInterface
    {
        $statusCode = http_response_code();

        ob_start();
        $this->whoops->{Run::EXCEPTION_HANDLER}($exception);
        $response = $this->responseFactory->createResponse(
            ($statusCode < 400 || $statusCode > 600) ? self::STATUS_INTERNAL_SERVER_ERROR : $statusCode
        );
        $response->getBody()->write(ob_get_clean());

        return $response;
    }
}
