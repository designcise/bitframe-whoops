<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Whoops\Handler\{HandlerInterface, XmlResponseHandler};

class XmlHandlerProvider extends AbstractProvider
{
    public const MIMES = ['text/xml', 'application/xml', 'application/x-xml'];

    public function getHandler(): HandlerInterface
    {
        $handler = new XmlResponseHandler();
        $handler->addTraceToOutput(true);

        return $handler;
    }

    public function getPreferredContentType(): string
    {
        return self::MIMES[0];
    }
}
