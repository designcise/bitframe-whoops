<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2022 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Test\Provider;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Provider\JsonHandlerProvider;

/**
 * @covers \BitFrame\Whoops\Provider\JsonHandlerProvider
 */
class JsonHandlerProviderTest extends TestCase
{
    private MockObject|ServerRequestInterface $request;

    public function setUp(): void
    {
        $this->request = $this->getMockBuilder(ServerRequestInterface::class)
            ->getMock();
    }

    public function testGetHandler(): void
    {
        $handlerProvider = new JsonHandlerProvider();
        $handler = $handlerProvider->getHandler($this->request);

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    public function testGetPreferredContentType(): void
    {
        $handler = new JsonHandlerProvider();

        $this->assertSame(
            'application/json',
            $handler->getPreferredContentType($this->request)
        );
    }
}
