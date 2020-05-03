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
use Prophecy\Argument;
use Psr\Http\Message\{
    ResponseFactoryInterface,
    ServerRequestInterface,
    ResponseInterface,
    StreamInterface
};
use Whoops\Exception\ErrorException;
use BitFrame\Whoops\Test\Asset\MiddlewareHandler;
use BitFrame\Whoops\ErrorHandler;

use InvalidArgumentException;

/**
 * @covers \BitFrame\Whoops\ErrorHandler
 */
class ErrorHandlerTest extends TestCase
{
    public function errorProvider(): array
    {
        return [
            'USER_ERROR' => [
                fn () => trigger_error('random error', E_USER_ERROR),
                ['status' => 500, 'type' => ErrorException::class, 'message' => 'random error'],
            ],
            'exception' => [
                function () {
                    throw new InvalidArgumentException('random exception');
                },
                ['status' => 500, 'type' => InvalidArgumentException::class, 'message' => 'random exception'],
            ],
        ];
    }

    /**
     * @dataProvider errorProvider
     *
     * @param callable $middleware
     * @param array $expectedError
     */
    public function testProcess(callable $middleware, array $expectedError): void
    {
        /** @var \Prophecy\Prophecy\ObjectProphecy|ServerRequestInterface $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('accept')->willReturn(['application/json']);

        $phpunit = $this;

        /** @var \Prophecy\Prophecy\ObjectProphecy|StreamInterface $stream */
        $stream = $this->prophesize(StreamInterface::class);
        $stream->write(Argument::any())->will(
            function ($args) use ($stream, $phpunit, $expectedError) {
                $error = json_decode($args[0], true)['error'] ?? [];

                $phpunit->assertSame($expectedError['type'], $error['type']);
                $phpunit->assertSame($expectedError['message'], $error['message']);

                return $stream->reveal();
            }
        );

        /** @var \Prophecy\Prophecy\ObjectProphecy|ResponseInterface $response */
        $response = $this->prophesize(ResponseInterface::class);
        $response->withHeader('Content-Type', 'application/json')->willReturn($response->reveal());
        $response->getBody()->willReturn($stream);

        /** @var \Prophecy\Prophecy\ObjectProphecy|ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory
            ->createResponse(Argument::any(), Argument::any())
            ->will(
                function ($args) use ($response, $phpunit, $expectedError) {
                    $phpunit->assertSame($expectedError['status'], $args[0]);

                    return $response->reveal();
                }
            );

        $middlewares = [
            new ErrorHandler($responseFactory->reveal(), [
                'setJsonApi' => false,
            ]),
            $middleware,
        ];

        /** @var \Prophecy\Prophecy\ObjectProphecy|ResponseFactoryInterface $responseFactory */
        $responseFactory2 = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory2->createResponse(Argument::any(), Argument::any())->willReturn($response->reveal());

        $handler = new MiddlewareHandler($middlewares, $responseFactory2->reveal());
        $response = $handler->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    /**
     * @runInSeparateProcess
     */
    public function testCatchGlobalErrors(): void
    {
        /** @var \Prophecy\Prophecy\ObjectProphecy|ServerRequestInterface $request */
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getHeader('accept')->willReturn(['application/json']);

        $phpunit = $this;
        $expectedError = [
            'status' => 500,
            'type' => InvalidArgumentException::class,
            'message' => 'random exception',
        ];

        /** @var \Prophecy\Prophecy\ObjectProphecy|StreamInterface $stream */
        $stream = $this->prophesize(StreamInterface::class);
        $stream->write(Argument::any())->will(
            function ($args) use ($stream, $phpunit, $expectedError) {
                $error = json_decode($args[0], true)['error'] ?? [];

                $phpunit->assertSame($expectedError['type'], $error['type']);
                $phpunit->assertSame($expectedError['message'], $error['message']);

                return $stream->reveal();
            }
        );

        /** @var \Prophecy\Prophecy\ObjectProphecy|ResponseInterface $response */
        $response = $this->prophesize(ResponseInterface::class);
        $response->withHeader('Content-Type', 'application/json')->willReturn($response->reveal());
        $response->getBody()->willReturn($stream);

        /** @var \Prophecy\Prophecy\ObjectProphecy|ResponseFactoryInterface $responseFactory */
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory
            ->createResponse(Argument::any(), Argument::any())
            ->will(
                function ($args) use ($response, $phpunit, $expectedError) {
                    $phpunit->assertSame($expectedError['status'], $args[0]);

                    return $response->reveal();
                }
            );

        $middlewares = [
            new ErrorHandler($responseFactory->reveal(), [
                'catchGlobalErrors' => true,
                'setJsonApi' => false,
            ]),
            function () use ($expectedError) {
                http_response_code($expectedError['status']);
                throw new InvalidArgumentException($expectedError['message']);
            },
        ];

        /** @var \Prophecy\Prophecy\ObjectProphecy|ResponseFactoryInterface $responseFactory */
        $responseFactory2 = $this->prophesize(ResponseFactoryInterface::class);
        $responseFactory2->createResponse(Argument::any(), Argument::any())->willReturn($response->reveal());

        $handler = new MiddlewareHandler($middlewares, $responseFactory2->reveal());
        $response = $handler->handle($request->reveal());

        $this->assertInstanceOf(ResponseInterface::class, $response);
    }

    public function testGetOptions(): void
    {
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $options = [
            'addTraceToOutput' => true,
            'setJsonApi' => false,
        ];

        $errorHandler = new ErrorHandler($responseFactory->reveal(), $options);

        $this->assertSame($options, $errorHandler->getOptions());
    }
}
