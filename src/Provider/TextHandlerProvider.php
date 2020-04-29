<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Whoops\Handler\{HandlerInterface, PlainTextHandler};

class TextHandlerProvider implements ProviderInterface
{
    public const MIMES = ['text/plain'];

    public function getHandler(): HandlerInterface
    {
        $handler = new PlainTextHandler();
        $handler->addTraceToOutput(true);

        return $handler;
    }

    public function getPreferredContentType(): string
    {
        return self::MIMES[0];
    }
}
