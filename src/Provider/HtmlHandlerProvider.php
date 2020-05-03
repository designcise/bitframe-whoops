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

class HtmlHandlerProvider extends AbstractProvider
{
    public const MIMES = ['text/html', 'application/xhtml+xml'];

    public function getHandler(): HandlerInterface
    {
        $request = $this->getRequest();
        $handler = new PrettyPageHandler();

        $handler->addDataTable('Application/Request Data', [
            'HTTP Method' => $request->getMethod(),
            'URI' => (string) $request->getUri(),
            'Script' => $request->getServerParams()['SCRIPT_NAME'],
            'Headers' => $request->getHeaders(),
            'Cookies' => $request->getCookieParams(),
            'Attributes' => $request->getAttributes(),
            'Query String Arguments' => $request->getQueryParams(),
            'Body Params' => $request->getParsedBody(),
        ]);

        return $handler;
    }

    public function getPreferredContentType(): string
    {
        return self::MIMES[0];
    }
}
