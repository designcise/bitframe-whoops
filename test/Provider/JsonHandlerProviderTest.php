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
use BitFrame\Whoops\Provider\JsonHandlerProvider;

/**
 * @covers \BitFrame\Whoops\Provider\JsonHandlerProvider
 */
class JsonHandlerProviderTest extends TestCase
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
        $handlerProvider = new JsonHandlerProvider($this->request);
        $handler = $handlerProvider->getHandler();

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    public function testGetPreferredContentType(): void
    {
        $handler = new JsonHandlerProvider($this->request);

        $this->assertSame('application/json', $handler->getPreferredContentType());
    }
}
