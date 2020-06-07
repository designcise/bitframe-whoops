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
use BitFrame\Whoops\Provider\{HandlerProviderNegotiator, ProviderInterface};
use Throwable;
use InvalidArgumentException;

use function is_a;
use function set_error_handler;
use function restore_error_handler;
use function ob_start;
use function ob_get_clean;

class ErrorHandler implements MiddlewareInterface
{
    use HandlerOptionsAwareTrait;

    /** @var int */
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

    private RunInterface $whoops;

    private ResponseFactoryInterface $responseFactory;

    /** @var ProviderInterface|string */
    private $handlerProvider;

    private array $options;

    private bool $catchGlobalErrors;

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

        $this->whoops = new Run();
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $handlerProvider = ($this->handlerProvider instanceof ProviderInterface)
            ? $this->handlerProvider
            : new $this->handlerProvider();
        $errorHandler = $handlerProvider->getHandler($request);

        $this->applyOptions($errorHandler);
        $this->whoops->pushHandler($errorHandler);

        $this->whoops->allowQuit(false);
        $this->whoops->writeToOutput(true);

        if ($this->catchGlobalErrors) {
            $this->whoops->register();
        } else {
            set_error_handler([$this->whoops, Run::ERROR_HANDLER]);
        }

        try {
            $response = $handler->handle($request);
        } catch (Throwable $e) {
            $response = $this->handleException($e)
                ->withHeader('Content-Type', $handlerProvider->getPreferredContentType($request));
        }

        if (! $this->catchGlobalErrors) {
            restore_error_handler();
        }

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
