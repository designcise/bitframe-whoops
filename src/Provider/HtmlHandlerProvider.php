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
use Whoops\Handler\{HandlerInterface, PrettyPageHandler};

class HtmlHandlerProvider implements ProviderInterface
{
    /** @var string */
    public const DATA_TABLE_NAME = 'Request Data';

    /** @var string[] */
    public const MIMES = ['text/html', 'application/xhtml+xml'];

    public function getHandler(ServerRequestInterface $request): HandlerInterface
    {
        $handler = new PrettyPageHandler();

        $handler->addDataTable(self::DATA_TABLE_NAME, [
            'HTTP Method' => $request->getMethod(),
            'URI' => (string) $request->getUri(),
            'Script' => $request->getServerParams()['SCRIPT_NAME'] ?? '',
            'Headers' => $request->getHeaders(),
            'Cookies' => $request->getCookieParams(),
            'Attributes' => $request->getAttributes(),
            'Query String' => $request->getQueryParams(),
            'Parsed Body' => $request->getParsedBody(),
        ]);

        return $handler;
    }

    public function getPreferredContentType(ServerRequestInterface $request): string
    {
        return self::MIMES[0];
    }
}
