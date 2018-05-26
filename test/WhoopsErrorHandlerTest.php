<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @license   https://github.com/designcise/bitframe-whoops/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\Test;

use \PHPUnit\Framework\TestCase;

use \Psr\Http\Message\{ServerRequestInterface, ResponseInterface};
use \Psr\Http\Server\RequestHandlerInterface;

use \BitFrame\Factory\HttpMessageFactory;
use \BitFrame\ErrorHandler\{ErrorHandlerInterface, WhoopsErrorHandler};

/**
 * @covers \BitFrame\ErrorHandler\WhoopsErrorHandler
 */
class WhoopsErrorHandlerTest extends TestCase
{
    /** @var \BitFrame\ErrorHandler\WhoopsErrorHandler */
    private $errorHandler;
    
    /** @var \Psr\Http\Message\ServerRequestInterface */
    private $request;

    protected function setUp()
    {
        $this->errorHandler = new WhoopsErrorHandler(function() {
            // do not output any error
            return \Whoops\Handler\Handler::QUIT;
        });
        $this->request = HttpMessageFactory::createServerRequest();
    }

    public function testProcessMiddleware()
    {
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->setMethods(['handle'])->getMock();
        $handler->method('handle')->willReturn(HttpMessageFactory::createResponse(200));

        $response = $this->errorHandler->process($this->request, $handler);
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    public function testProcessMiddlewareWith403Exception()
    {
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->setMethods(['handle'])->getMock();
        $handler->method('handle')->willThrowException(new \BitFrame\Exception\ForbiddenException());

        $response = $this->errorHandler->process($this->request, $handler);
        $this->assertEquals(403, $response->getStatusCode());
    }
    
    public function testProcessMiddlewareWithDefaultException()
    {
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->setMethods(['handle'])->getMock();
        $handler->method('handle')->willThrowException(new \Exception());

        $response = $this->errorHandler->process($this->request, $handler);
        $this->assertEquals(500, $response->getStatusCode());
    }
    
    public function testWhoopsErrorHandlerErrorException()
    {
        try {
            $run = $this->errorHandler->getWhoopsInstance($this->request);
            $run->handleError(E_USER_ERROR, 'Testing', 'Test File', 44);
            $this->fail("Missing expected exception");
        } catch (\ErrorException $e) {
            $this->assertSame(E_USER_ERROR, $e->getSeverity());
            // see https://github.com/filp/whoops/issues/267
            $this->assertSame(E_USER_ERROR, $e->getCode(), "For BC reasons getCode() should match getSeverity()");
            $this->assertSame('Testing', $e->getMessage());
            $this->assertSame('Test File', $e->getFile());
            $this->assertSame(44, $e->getLine());
        }
    }
    
    public function testProcessMiddlewareWithTriggerError()
    {
        $response = $this->errorHandler->process($this->request, new class($this) implements RequestHandlerInterface {
            /** @var \PHPUnit\Framework\TestCase */
            private $test;
            
            public function __construct($testCaseInstance)
            {
                $this->test = $testCaseInstance;
            }
            
            public function handle(ServerRequestInterface $request): ResponseInterface 
            {
                trigger_error('Testing', E_USER_ERROR);
                
                return HttpMessageFactory::createResponse(200);
            }
        });
        
        $this->assertEquals(500, $response->getStatusCode());
    }
}
