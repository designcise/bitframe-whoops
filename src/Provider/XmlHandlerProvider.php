<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\{HandlerInterface, XmlResponseHandler};

class XmlHandlerProvider implements ProviderInterface
{
    /** @var string[] */
    public const MIMES = ['text/xml', 'application/xml', 'application/x-xml'];

    public function getHandler(ServerRequestInterface $request): HandlerInterface
    {
        return new XmlResponseHandler();
    }

    public function getPreferredContentType(ServerRequestInterface $request): string
    {
        return self::MIMES[0];
    }
}
