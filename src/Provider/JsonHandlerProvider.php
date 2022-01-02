<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2022 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\{HandlerInterface, JsonResponseHandler};

class JsonHandlerProvider implements ProviderInterface
{
    /** @var string[] */
    public const MIMES = ['application/json', 'text/json', 'application/x-json'];

    public function getHandler(ServerRequestInterface $request): HandlerInterface
    {
        return new JsonResponseHandler();
    }

    public function getPreferredContentType(ServerRequestInterface $request): string
    {
        return self::MIMES[0];
    }
}
