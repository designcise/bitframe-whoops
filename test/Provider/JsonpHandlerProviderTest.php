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

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
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
        array $query,
    ): void {
        $handlerProvider = new JsonpHandlerProvider();

        $this->expectException(RuntimeException::class);

        $handlerProvider->getHandler($this->getRequest($method, $query));
    }

    public function testGetHandler(): void
    {
        $handlerProvider = new JsonpHandlerProvider();
        $handler = $handlerProvider->getHandler($this->getRequest());

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    public function testGetPreferredContentType(): void
    {
        $handler = new JsonpHandlerProvider();

        $this->assertSame(
            'application/javascript',
            $handler->getPreferredContentType($this->getRequest())
        );
    }

    private function getRequest(
        string $method = 'GET',
        array $query = ['callback' => 'foo'],
    ): MockObject|ServerRequestInterface {
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getMethod', 'getQueryParams'])
            ->getMockForAbstractClass();

        $request->method('getMethod')->willReturn($method);
        $request->method('getQueryParams')->willReturn($query);

        return $request;
    }
}
