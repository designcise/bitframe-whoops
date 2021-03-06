<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Test\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Provider\HtmlHandlerProvider;

/**
 * @covers \BitFrame\Whoops\Provider\HtmlHandlerProvider
 */
class HtmlHandlerProviderTest extends TestCase
{
    public function testGetHandler(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods([
                'getMethod',
                'getUri',
                'getServerParams',
                'getHeaders',
                'getCookieParams',
                'getAttributes',
                'getQueryParams',
                'getParsedBody'
            ])
            ->getMockForAbstractClass();

        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn('https://bitframephp.com');
        $request->method('getServerParams')->willReturn(['SCRIPT_NAME' => 'hello world']);
        $request->method('getHeaders')->willReturn(['test' => '1234']);
        $request->method('getCookieParams')->willReturn([]);
        $request->method('getAttributes')->willReturn(['foo' => 'bar']);
        $request->method('getQueryParams')->willReturn(['bar' => 'baz']);
        $request->method('getParsedBody')->willReturn([]);

        $handlerProvider = new HtmlHandlerProvider();
        /** @var \Whoops\Handler\PrettyPageHandler $handler */
        $handler = $handlerProvider->getHandler($request);

        $this->assertInstanceOf(HandlerInterface::class, $handler);
        $this->assertSame([
            'HTTP Method' => 'GET',
            'URI' => 'https://bitframephp.com',
            'Script' => 'hello world',
            'Headers' => ['test' => '1234'],
            'Cookies' => [],
            'Attributes' => ['foo' => 'bar'],
            'Query String' => ['bar' => 'baz'],
            'Parsed Body' => [],
        ], $handler->getDataTables(HtmlHandlerProvider::DATA_TABLE_NAME));
    }

    public function testGetPreferredContentType(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();

        $handler = new HtmlHandlerProvider();

        $this->assertSame('text/html', $handler->getPreferredContentType($request));
    }
}
