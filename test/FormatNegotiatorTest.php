<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Test\Http;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use BitFrame\Whoops\FormatNegotiator;
use BitFrame\Whoops\Provider\{
    HtmlHandlerProvider,
    JsonHandlerProvider,
    TextHandlerProvider,
    XmlHandlerProvider
};

/**
 * @covers \BitFrame\Whoops\FormatNegotiator
 */
class FormatNegotiatorTest extends TestCase
{
    public function preferredMediaParserProvider(): array
    {
        return [
            'text/html' => ['text/html', HtmlHandlerProvider::class],
            'app/xhtml+xml' => ['application/xhtml+xml', HtmlHandlerProvider::class],

            'app/json' => ['application/json', JsonHandlerProvider::class],
            'text/json' => ['text/json', JsonHandlerProvider::class],
            'app/x-json' => ['application/x-json', JsonHandlerProvider::class],

            'text/xml' => ['text/xml', XmlHandlerProvider::class],
            'app/xml' => ['application/xml', XmlHandlerProvider::class],
            'app/x-xml' => ['application/x-xml', XmlHandlerProvider::class],

            'text/plain' => ['text/plain', TextHandlerProvider::class],
            'app/form-urlencoded' => ['application/x-www-form-urlencoded', HtmlHandlerProvider::class],
            'app/form-data' => ['multipart/form-data', HtmlHandlerProvider::class],
        ];
    }

    /**
     * @dataProvider preferredMediaParserProvider
     */
    public function testFromRequest(string $mime, string $expectedParser): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([$mime]);

        $this->assertInstanceOf($expectedParser, FormatNegotiator::fromRequest($request));
    }

    public function testGetsDefaultParserWhenAcceptHeaderNotPresent(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([]);

        $this->assertInstanceOf(HtmlHandlerProvider::class, FormatNegotiator::fromRequest($request));
    }
}
