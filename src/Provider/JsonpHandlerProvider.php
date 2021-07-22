<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2021 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Handler\JsonpResponseHandler;
use RuntimeException;

class JsonpHandlerProvider implements ProviderInterface
{
    /** @var string[] */
    public const MIMES = ['application/javascript'];

    public function getHandler(ServerRequestInterface $request): HandlerInterface
    {
        $queryParams = $request->getQueryParams();
        $method = $request->getMethod();

        if (! isset($queryParams['callback']) || ($method !== 'GET' && $method !== 'HEAD')) {
            throw new RuntimeException(
                'JSONP request must be a GET or HEAD request with a "callback" parameter'
            );
        }

        return new JsonpResponseHandler($queryParams['callback']);
    }

    public function getPreferredContentType(ServerRequestInterface $request): string
    {
        return self::MIMES[0];
    }
}
