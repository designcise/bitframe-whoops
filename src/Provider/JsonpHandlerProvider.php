<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Handler\JsonpResponseHandler;
use RuntimeException;

class JsonpHandlerProvider extends AbstractProvider
{
    /** @var string[] */
    public const MIMES = ['application/javascript'];

    public function getHandler(): HandlerInterface
    {
        $request = $this->getRequest();
        $queryParams = $request->getQueryParams();
        $method = $request->getMethod();

        if (! isset($queryParams['callback']) || ($method !== 'GET' && $method !== 'HEAD')) {
            throw new RuntimeException(
                'JSONP request must be a GET or HEAD request with a "callback" parameter'
            );
        }

        return new JsonpResponseHandler($queryParams['callback']);
    }

    public function getPreferredContentType(): string
    {
        return self::MIMES[0];
    }
}
