<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2023 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Test\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\{HandlerInterface, Handler};
use BitFrame\Whoops\Provider\{
    ProviderInterface,
    HandlerProviderNegotiator,
    HtmlHandlerProvider,
    JsonHandlerProvider,
    TextHandlerProvider,
    XmlHandlerProvider};
use InvalidArgumentException;

use function sprintf;
use function get_class;

/**
 * @covers \BitFrame\Whoops\Provider\HandlerProviderNegotiator
 */
class HandlerProviderNegotiatorTest extends TestCase
{
    public function parserNameProvider(): array
    {
        return [
            'add new parser' => ['newHandlerProvider'],
            'replace HtmlHandlerProvider' => [HandlerProviderNegotiator::HTML],
            'replace JsonHandlerProvider' => [HandlerProviderNegotiator::JSON],
            'replace JsonpHandlerProvider' => [HandlerProviderNegotiator::JSONP],
            'replace TextHandlerProvider' => [HandlerProviderNegotiator::TEXT],
            'replace XmlHandlerProvider' => [HandlerProviderNegotiator::XML],
        ];
    }

    /**
     * @runInSeparateProcess
     * @dataProvider parserNameProvider
     *
     * @param string $handlerProvider
     */
    public function testAddNewOrUpdateExistingHandlerProvider(string $handlerProvider): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn(['text/made-up']);

        $simpleHandlerProvider = $this->getSimpleHandlerProvider($request);
        $negotiator = new HandlerProviderNegotiator();

        $negotiator->add($handlerProvider, get_class($simpleHandlerProvider));

        $this->assertEquals($simpleHandlerProvider->getHandler($request), $negotiator->getHandler($request));

        $this->assertSame(
            $negotiator->getPreferredContentType($request),
            $simpleHandlerProvider->getPreferredContentType($request)
        );
    }

    public function testAddNewInvalidProviderShouldThrowException(): void
    {
        $invalidProvider = new class {};
        $negotiator = new HandlerProviderNegotiator();

        $this->expectException(InvalidArgumentException::class);

        $negotiator->add('whatever', get_class($invalidProvider));
    }

    public function preferredHandlerProviderProvider(): array
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
     * @dataProvider preferredHandlerProviderProvider
     *
     * @param string $mime
     * @param string $expectedProvider
     */
    public function testGetPreferredProvider(string $mime, string $expectedProvider): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([$mime]);

        $negotiator = new HandlerProviderNegotiator();

        $this->assertInstanceOf($expectedProvider, $negotiator->getPreferredProvider($request));
    }

    public function testGetsDefaultProviderWhenAcceptHeaderNotPresent(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([]);

        $negotiator = new HandlerProviderNegotiator();

        $this->assertInstanceOf(
            HtmlHandlerProvider::class,
            $negotiator->getPreferredProvider($request)
        );
    }

    public function testGetsCachedProviderOnRepeatCalls(): void
    {
        /** @var MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([]);

        $negotiator = new HandlerProviderNegotiator();

        $preferredProvider = $negotiator->getPreferredProvider($request);

        $this->assertSame($preferredProvider, $negotiator->getPreferredProvider($request));
    }

    private function getSimpleHandlerProvider(ServerRequestInterface $request): ProviderInterface
    {
        return new class () implements ProviderInterface {
            public const MIMES = ['text/made-up'];

            public function getHandler(ServerRequestInterface $request): HandlerInterface
            {
                return new class extends Handler {
                    public function handle(): void
                    {
                        $exception = $this->getException();
                        $message = sprintf(
                            "%s: %s in file %s on line %d",
                            get_class($exception),
                            $exception->getMessage(),
                            $exception->getFile(),
                            $exception->getLine()
                        );

                        echo $message;
                    }
                };
            }

            public function getPreferredContentType(ServerRequestInterface $request): string
            {
                return self::MIMES[0];
            }
        };
    }
}
