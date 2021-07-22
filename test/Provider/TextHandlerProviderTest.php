<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2021 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Test\Provider;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Provider\TextHandlerProvider;

/**
 * @covers \BitFrame\Whoops\Provider\TextHandlerProvider
 */
class TextHandlerProviderTest extends TestCase
{
    private MockObject|ServerRequestInterface $request;

    public function setUp(): void
    {
        $this->request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
    }

    public function testGetHandler(): void
    {
        $handlerProvider = new TextHandlerProvider();
        $handler = $handlerProvider->getHandler($this->request);

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    public function testGetPreferredContentType(): void
    {
        $handler = new TextHandlerProvider();

        $this->assertSame(
            'text/plain',
            $handler->getPreferredContentType($this->request)
        );
    }
}
