<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Whoops\Handler\{HandlerInterface, PrettyPageHandler};

class HtmlHandlerProvider implements ProviderInterface
{
    public const MIMES = ['text/html', 'application/xhtml+xml'];

    public function getHandler(): HandlerInterface
    {
        return new PrettyPageHandler();
    }

    public function getPreferredContentType(): string
    {
        return self::MIMES[0];
    }
}
