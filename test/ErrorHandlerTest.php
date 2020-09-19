<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Test\Http;

use ReflectionObject;
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
use BitFrame\Whoops\Provider\HandlerProviderNegotiator;
use InvalidArgumentException;

use function trigger_error;
use function json_decode;
use function http_response_code;
use function get_class;

/**
 * @covers \BitFrame\Whoops\ErrorHandler
 */
class ErrorHandlerTest extends TestCase
{
    public function testFromNegotiator(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResponseFactoryInterface $factory */
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
        /** @var \PHPUnit\Framework\MockObject\MockObject|ResponseFactoryInterface $factory */
        $factory = $this->getMockBuilder(ResponseFactoryInterface::class)
            ->getMockForAbstractClass();

        $this->expectException(InvalidArgumentException::class);

        new ErrorHandler($factory, get_class($this));
    }

    public function testGetOptions(): void
    {
        $responseFactory = $this->prophesize(ResponseFactoryInterface::class);
        $options = [
            'addTraceToOutput' => true,
            'setJsonApi' => false,
        ];

        $errorHandler = new ErrorHandler(
            $responseFactory->reveal(),
            HandlerProviderNegotiator::class,
            $options
        );

        $this->assertSame($options, $errorHandler->getOptions());
    }
}
