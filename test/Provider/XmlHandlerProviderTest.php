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
use BitFrame\Whoops\Provider\XmlHandlerProvider;

/**
 * @covers \BitFrame\Whoops\Provider\XmlHandlerProvider
 */
class XmlHandlerProviderTest extends TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ServerRequestInterface */
    private $request;

    public function setUp(): void
    {
        $this->request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
    }

    public function testGetHandler(): void
    {
        $handlerProvider = new XmlHandlerProvider();
        $handler = $handlerProvider->getHandler($this->request);

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    public function testGetPreferredContentType(): void
    {
        $handler = new XmlHandlerProvider();

        $this->assertSame(
            'text/xml',
            $handler->getPreferredContentType($this->request)
        );
    }
}
