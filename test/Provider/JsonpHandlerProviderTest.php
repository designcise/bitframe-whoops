<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Test\Provider;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Provider\JsonpHandlerProvider;
use RuntimeException;

/**
 * @covers \BitFrame\Whoops\Provider\JsonpHandlerProvider
 */
class JsonpHandlerProviderTest extends TestCase
{
    public function invalidRequestPropsProvider(): array
    {
        return [
            'POST' => ['POST', ['callback' => 'foo']],
            'PUT' => ['PUT', ['callback' => 'foo']],
            'DELETE' => ['DELETE', ['callback' => 'foo']],
            'CONNECT' => ['CONNECT', ['callback' => 'foo']],
            'OPTIONS' => ['OPTIONS', ['callback' => 'foo']],
            'TRACE' => ['TRACE', ['callback' => 'foo']],
            'PATCH' => ['PATCH', ['callback' => 'foo']],
            'POST with invalid callback' => ['POST', ['invalid' => 'foo']],
            'PUT with invalid callback' => ['PUT', ['invalid' => 'foo']],
            'DELETE with invalid callback' => ['DELETE', ['invalid' => 'foo']],
            'CONNECT with invalid callback' => ['CONNECT', ['invalid' => 'foo']],
            'OPTIONS with invalid callback' => ['OPTIONS', ['invalid' => 'foo']],
            'TRACE with invalid callback' => ['TRACE', ['invalid' => 'foo']],
            'PATCH with invalid callback' => ['PATCH', ['invalid' => 'foo']],
        ];
    }

    /**
     * @dataProvider invalidRequestPropsProvider
     *
     * @param string $method
     * @param array $query
     */
    public function testNonGetOrHeadMethodRequestShouldThrowException(
        string $method,
        array $query
    ): void {
        $handlerProvider = new JsonpHandlerProvider($this->getRequest($method, $query));

        $this->expectException(RuntimeException::class);

        $handlerProvider->getHandler();
    }

    public function testGetHandler(): void
    {
        $handlerProvider = new JsonpHandlerProvider($this->getRequest());
        $handler = $handlerProvider->getHandler();

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    public function testGetPreferredContentType(): void
    {
        $handler = new JsonpHandlerProvider($this->getRequest());

        $this->assertSame('application/javascript', $handler->getPreferredContentType());
    }

    /**
     * @param string $method
     * @param array $query
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface
     */
    private function getRequest(string $method = 'GET', array $query = ['callback' => 'foo'])
    {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getMethod', 'getQueryParams'])
            ->getMockForAbstractClass();

        $request->method('getMethod')->willReturn($method);
        $request->method('getQueryParams')->willReturn($query);

        return $request;
    }
}
