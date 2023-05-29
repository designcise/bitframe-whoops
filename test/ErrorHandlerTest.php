<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2023 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops {
    /**
     * Overwriting default `header()` function.
     *
     * @param string $header
     * @param bool $replace
     * @param int $responseCode
     */
    function header(string $header, bool $replace = true, int $responseCode = 200): void
    {
        echo "{$responseCode} {$header}";
    }
}

namespace BitFrame\Whoops\Test {
    use PHPUnit\Framework\MockObject\MockObject;
    use ReflectionObject;
    use PHPUnit\Framework\TestCase;
    use Mockery;
    use Psr\Http\Message\{
        ResponseFactoryInterface,
        ServerRequestInterface,
        ResponseInterface,
        StreamInterface
    };
    use BitFrame\Whoops\Test\Asset\MiddlewareHandler;
    use BitFrame\Whoops\ErrorHandler;
    use BitFrame\Whoops\Provider\HandlerProviderNegotiator;
    use InvalidArgumentException;
    use Whoops\Handler\{Handler, HandlerInterface};
    use Whoops\RunInterface;
    use Whoops\Util\Misc;
    use Whoops\Util\SystemFacade;
    use Whoops\Exception\ErrorException;

    use function trigger_error;
    use function json_decode;
    use function http_response_code;
    use function get_class;

    use const E_ERROR;

    /**
     * @covers \BitFrame\Whoops\ErrorHandler
     */
    class ErrorHandlerTest extends TestCase
    {
        public function tearDown(): void
        {
            Mockery::close();
        }

        public function testFromNegotiator(): void
        {
            /** @var MockObject|ResponseFactoryInterface $factory */
            $factory = $this->getMockBuilder(ResponseFactoryInterface::class)
                ->getMockForAbstractClass();

            $options = [
                'catchGlobalErrors' => true,
                'setJsonApi' => false,
            ];

            $errorHandler = ErrorHandler::fromNegotiator($factory, $options);

            $errorHandlerReflection = new ReflectionObject($errorHandler);
            $handlerProvider = $errorHandlerReflection->getProperty('handlerProvider');
            $handlerProvider->setAccessible(true);

            $this->assertSame(
                HandlerProviderNegotiator::class,
                $handlerProvider->getValue($errorHandler)
            );
            $this->assertSame($options, $errorHandler->getOptions());
        }

        public function testShouldThrowExceptionWhenInvalidHandlerProviderClassProvided(): void
        {
            /** @var MockObject|ResponseFactoryInterface $factory */
            $factory = $this->getMockBuilder(ResponseFactoryInterface::class)
                ->getMockForAbstractClass();

            $this->expectException(InvalidArgumentException::class);

            new ErrorHandler($factory, get_class($this));
        }

        public function errorProvider(): array
        {
            return [
                'USER_ERROR' => [
                    fn() => trigger_error('random error', E_USER_ERROR),
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
            $request = $this->getMockedJsonRequest();

            $phpunit = $this;
            $stream = $this->getMockedErrorMsgStream($phpunit, $expectedError);
            $response = $this->getMockedResponseWithStream($stream);
            $responseFactory = $this->getMockedResponseFactory(function ($status) use ($response, $phpunit, $expectedError) {
                $phpunit->assertSame($expectedError['status'], $status);
                return $response;
            });

            $middlewares = [
                new ErrorHandler($responseFactory, new HandlerProviderNegotiator(), [
                    'setJsonApi' => false,
                ]),
                $middleware,
            ];

            $responseFactory2 = $this->getMockedResponseFactory($response);

            $handler = new MiddlewareHandler($middlewares, $responseFactory2);
            $response = $handler->handle($request);

            $this->assertInstanceOf(ResponseInterface::class, $response);
        }

        /**
         * @runInSeparateProcess
         */
        public function testCatchGlobalErrors(): void
        {
            $request = $this->getMockedJsonRequest();

            $phpunit = $this;
            $expectedError = [
                'status' => 420,
                'type' => InvalidArgumentException::class,
                'message' => 'random exception',
            ];

            $stream = $this->getMockedErrorMsgStream($phpunit, $expectedError);
            $response = $this->getMockedResponseWithStream($stream);
            $responseFactory = $this->getMockedResponseFactory(function ($status) use ($response, $phpunit, $expectedError) {
                $phpunit->assertSame($expectedError['status'], $status);
                return $response;
            });

            $middlewares = [
                new ErrorHandler($responseFactory, HandlerProviderNegotiator::class, [
                    'catchGlobalErrors' => true,
                    'setJsonApi' => false,
                ]),
                function () use ($expectedError) {
                    http_response_code($expectedError['status']);
                    throw new InvalidArgumentException($expectedError['message']);
                },
            ];

            $responseFactory2 = $this->getMockedResponseFactory($response);

            $handler = new MiddlewareHandler($middlewares, $responseFactory2);
            $response = $handler->handle($request);

            $this->assertInstanceOf(ResponseInterface::class, $response);
            $this->expectOutputRegex('/.*"error":{"type":"InvalidArgumentException","message":"random exception",.*/');
        }

        /**
         * @throws ErrorException
         */
        public function testHandleErrorWhenLevelIsNotSupported(): void
        {
            /** @var MockObject|ResponseFactoryInterface $factory */
            $factory = $this->getMockBuilder(ResponseFactoryInterface::class)
                ->getMockForAbstractClass();

            /* @var Mockery\Mock|ErrorHandler $errorHandler */
            $errorHandler = new ErrorHandler($factory, HandlerProviderNegotiator::class);

            /** @var Mockery\Mock|SystemFacade $system */
            $system = Mockery::mock(SystemFacade::class)->makePartial();
            $system->shouldReceive('getErrorReportingLevel')->andReturn(0);

            $this->setProperties($errorHandler, ['system' => $system]);

            $this->assertFalse($errorHandler->handleError(0, 'test'));
        }

        /**
         * @runInSeparateProcess
         */
        public function testHandleShutdown(): void
        {
            /** @var MockObject|ResponseFactoryInterface $factory */
            $factory = $this->getMockBuilder(ResponseFactoryInterface::class)
                ->getMockForAbstractClass();

            $errorHandler = new ErrorHandler(
                $factory,
                HandlerProviderNegotiator::class,
                ['catchGlobalErrors' => true]
            );

            /** @var Mockery\Mock|SystemFacade $system */
            $system = Mockery::mock(SystemFacade::class)->makePartial();
            $system->shouldReceive('getLastError')->andReturn([
                'type' => E_ERROR,
                'message' => 'Undefined variable: x',
                'file' => 'path/to/file/index.php',
                'line' => 2,
            ]);
            $system->shouldReceive('getErrorReportingLevel')->andReturn(E_ALL);

            /** @var Mockery\Mock|HandlerInterface $handler */
            $handler = Mockery::mock(Handler::class)->makePartial();;
            $handler->shouldReceive('handle')->andReturnUsing(function () {
                echo 'foobar';
                return Handler::QUIT;
            });

            /** @var Mockery\Mock|RunInterface $run */
            $run = Mockery::mock(RunInterface::class)->makePartial();
            $run->shouldReceive('writeToOutput')->andReturn(true);
            $run->shouldReceive('sendHttpCode')->andReturn(500);
            $run->shouldReceive('getHandlers')->andReturn([$handler]);

            $this->setProperties($errorHandler, ['system' => $system, 'whoops' => $run]);

            $errorHandler->handleShutdown();

            $this->expectOutputString('foobar');
        }

        /**
         * @runInSeparateProcess
         */
        public function testCanSetStatusAndContentType(): void
        {
            /** @var MockObject|ResponseFactoryInterface $factory */
            $factory = $this->getMockBuilder(ResponseFactoryInterface::class)
                ->getMockForAbstractClass();

            $errorHandler = new ErrorHandler(
                $factory,
                HandlerProviderNegotiator::class,
                ['catchGlobalErrors' => true]
            );

            /** @var Mockery\Mock|SystemFacade $system */
            $system = Mockery::mock(SystemFacade::class)->makePartial();
            $system->shouldReceive('getErrorReportingLevel')->andReturn(E_ALL);

            /** @var MockObject|HandlerInterface $handler */
            $handler = $this->getMockBuilder(Handler::class)
                ->onlyMethods(['handle'])
                ->addMethods(['contentType'])
                ->getMockForAbstractClass();
            $handler->method('contentType')->willReturn('application/json');
            $handler->method('handle')->willReturn(Handler::QUIT);

            $miscMock = Mockery::mock('alias:' . Misc::class);
            $miscMock->allows()->canSendHeaders()->andReturns(true);

            /** @var Mockery\Mock|RunInterface $run */
            $run = Mockery::mock(RunInterface::class)->makePartial();
            $run->shouldReceive('writeToOutput')->andReturn(true);
            $run->shouldReceive('sendHttpCode')->andReturn(500);
            $run->shouldReceive('getHandlers')->andReturn([$handler]);

            $this->setProperties($errorHandler, ['system' => $system, 'whoops' => $run]);

            $errorHandler->handleException(new InvalidArgumentException('foobar'));

            $this->expectOutputString('500 Content-Type: application/json');
        }

        public function testGetOptions(): void
        {
            $responseFactory = Mockery::mock(ResponseFactoryInterface::class);
            $options = [
                'addTraceToOutput' => true,
                'setJsonApi' => false,
            ];

            $errorHandler = new ErrorHandler(
                $responseFactory,
                HandlerProviderNegotiator::class,
                $options
            );

            $this->assertSame($options, $errorHandler->getOptions());
        }

        /**
         * @param TestCase $phpunit
         * @param array $expectedError
         *
         * @return Mockery\Mock|StreamInterface
         */
        protected function getMockedErrorMsgStream(TestCase $phpunit, array $expectedError)
        {
            /** @var Mockery\Mock|StreamInterface $stream */
            $stream = Mockery::mock(StreamInterface::class)->makePartial();
            $stream->shouldReceive('write')->withAnyArgs()->andReturnUsing(
                function ($exception) use ($stream, $phpunit, $expectedError) {
                    $error = json_decode($exception, true)['error'] ?? [];

                    $phpunit->assertSame($expectedError['type'], $error['type']);
                    $phpunit->assertSame($expectedError['message'], $error['message']);

                    return strlen((string) $stream);
                }
            );

            return $stream;
        }

        /**
         * @param StreamInterface $stream
         *
         * @return Mockery\Mock|ResponseInterface
         */
        protected function getMockedResponseWithStream(StreamInterface $stream)
        {
            /** @var Mockery\Mock|ResponseInterface $response */
            $response = Mockery::mock(ResponseInterface::class)->makePartial();
            $response->allows()->withHeader('Content-Type', 'application/json')->andReturnSelf();
            $response->shouldReceive('getBody')->andReturn($stream);
            return $response;
        }

        /**
         * @return Mockery\Mock|ServerRequestInterface
         */
        protected function getMockedJsonRequest()
        {
            /** @var Mockery\Mock|ServerRequestInterface $request */
            $request = Mockery::mock(ServerRequestInterface::class)->makePartial();
            $request->allows()->getHeader('accept')->andReturns(['application/json']);
            return $request;
        }

        /**
         * @param mixed $return
         *
         * @return Mockery\Mock|ResponseFactoryInterface
         */
        protected function getMockedResponseFactory(mixed $return)
        {
            /** @var Mockery\Mock|ResponseFactoryInterface $responseFactory */
            $responseFactory = Mockery::mock(ResponseFactoryInterface::class)->makePartial();
            if (is_callable($return)) {
                $responseFactory->allows()->createResponse()->withAnyArgs()->andReturnUsing($return);
            } else {
                $responseFactory->allows()->createResponse()->withAnyArgs()->andReturns($return);
            }
            return $responseFactory;
        }

        private function setProperties(object $object, array $props)
        {
            $reflection = new ReflectionObject($object);

            foreach ($props as $name => $value) {
                $prop = $reflection->getProperty($name);
                $prop->setAccessible(true);
                $prop->setValue($object, $value);
            }
        }
    }
}
