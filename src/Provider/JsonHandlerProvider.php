<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Whoops\Handler\{HandlerInterface, JsonResponseHandler};

class JsonHandlerProvider extends AbstractProvider
{
    /** @var string[] */
    public const MIMES = ['application/json', 'text/json', 'application/x-json'];

    public function getHandler(): HandlerInterface
    {
        return new JsonResponseHandler();
    }

    public function getPreferredContentType(): string
    {
        return self::MIMES[0];
    }
}
