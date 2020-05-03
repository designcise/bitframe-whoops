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
use Whoops\{Run, RunInterface};

use function http_response_code;

class GlobalErrorHandler implements MiddlewareInterface
{
    use HandlerOptionsAwareTrait;

    /** @var int */
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

    private RunInterface $whoops;

    private array $options;

    public function __construct(array $options = [])
    {
        $this->whoops = new Run();
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $this->registerErrorHandler($request);
        return $handler->handle($request);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function registerErrorHandler(ServerRequestInterface $request): void
    {
        $format = FormatNegotiator::fromRequest($request);
        $errorHandler = $format->getHandler();
        $this->applyOptions($errorHandler);
        $this->whoops->pushHandler($errorHandler);

        $this->whoops->allowQuit(true);
        $this->whoops->writeToOutput(true);

        $this->whoops->pushHandler(function () {
            $statusCode = http_response_code();
            $this->whoops->sendHttpCode(
                ($statusCode < 400 || $statusCode > 600) ? self::STATUS_INTERNAL_SERVER_ERROR : $statusCode
            );
        });

        $this->whoops->register();
    }
}
