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
    /** @var int */
    private const STATUS_INTERNAL_SERVER_ERROR = 500;

    private RunInterface $whoops;

    public function __construct()
    {
        $this->whoops = new Run();
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

    private function registerErrorHandler(ServerRequestInterface $request): void
    {
        $format = FormatNegotiator::fromRequest($request);
        $this->whoops->pushHandler($format->getHandler());

        $this->whoops->allowQuit(true);
        $this->whoops->writeToOutput(true);

        $this->whoops->pushHandler(function () {
            $code = http_response_code();
            $this->whoops->sendHttpCode(
                ($code < 400 || $code > 600) ? self::STATUS_INTERNAL_SERVER_ERROR : $code
            );
        });

        $this->whoops->register();
    }
}
