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
use Whoops\Handler\{HandlerInterface, Handler};
use BitFrame\Whoops\Provider\{
    AbstractProvider,
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
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn(['text/made-up']);

        $simpleHandlerProvider = $this->getSimpleHandlerProvider($request);
        $negotiator = new HandlerProviderNegotiator($request);

        $negotiator->add($handlerProvider, get_class($simpleHandlerProvider));

        $this->assertEquals($simpleHandlerProvider->getHandler(), $negotiator->getHandler());

        $this->assertSame(
            $negotiator->getPreferredContentType(),
            $simpleHandlerProvider->getPreferredContentType()
        );
    }

    public function testAddNewInvalidProviderShouldThrowException(): void
    {
        $invalidProvider = new class {};

        /** @var ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMockForAbstractClass();
        $negotiator = new HandlerProviderNegotiator($request);

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
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([$mime]);

        $negotiator = new HandlerProviderNegotiator($request);

        $this->assertInstanceOf($expectedProvider, $negotiator->getPreferredProvider());
    }

    public function testGetsDefaultProviderWhenAcceptHeaderNotPresent(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([]);

        $negotiator = new HandlerProviderNegotiator($request);

        $this->assertInstanceOf(
            HtmlHandlerProvider::class,
            $negotiator->getPreferredProvider()
        );
    }

    public function testGetsCachedProviderOnRepeatCalls(): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface $request */
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->onlyMethods(['getHeader'])
            ->getMockForAbstractClass();

        $request
            ->method('getHeader')
            ->with('accept')
            ->willReturn([]);

        $negotiator = new HandlerProviderNegotiator($request);

        $preferredProvider = $negotiator->getPreferredProvider();

        $this->assertSame($preferredProvider, $negotiator->getPreferredProvider());
    }

    private function getSimpleHandlerProvider(ServerRequestInterface $request): AbstractProvider
    {
        return new class ($request) extends AbstractProvider {
            public const MIMES = ['text/made-up'];

            public function getHandler(): HandlerInterface
            {
                return new class extends Handler {
                    public function handle()
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

            public function getPreferredContentType(): string
            {
                return self::MIMES[0];
            }
        };
    }
}
