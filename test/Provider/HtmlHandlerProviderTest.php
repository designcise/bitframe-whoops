<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2023 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Test\Provider;

use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\{HandlerInterface, PrettyPageHandler};
use BitFrame\Whoops\Provider\HtmlHandlerProvider;
use Psr\Http\Message\UriInterface;

/**
 * @covers \BitFrame\Whoops\Provider\HtmlHandlerProvider
 */
class HtmlHandlerProviderTest extends TestCase
{
    public function testGetHandler(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
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

        /** @var MockObject|ServerRequestInterface $request */
        $uri = $this->getMockBuilder(UriInterface::class)
            ->onlyMethods(['__toString'])
            ->getMockForAbstractClass();

        $uri
            ->method('__toString')
            ->willReturn('https://bitframephp.com');

        $request->method('getMethod')->willReturn('GET');
        $request->method('getUri')->willReturn($uri);
        $request->method('getServerParams')->willReturn(['SCRIPT_NAME' => 'hello world']);
        $request->method('getHeaders')->willReturn(['test' => '1234']);
        $request->method('getCookieParams')->willReturn([]);
        $request->method('getAttributes')->willReturn(['foo' => 'bar']);
        $request->method('getQueryParams')->willReturn(['bar' => 'baz']);
        $request->method('getParsedBody')->willReturn([]);

        $handlerProvider = new HtmlHandlerProvider();
        /** @var PrettyPageHandler $handler */
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
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();

        $handler = new HtmlHandlerProvider();

        $this->assertSame('text/html', $handler->getPreferredContentType($request));
    }
}
