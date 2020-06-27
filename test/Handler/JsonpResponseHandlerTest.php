<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Test\Handler;

use PHPUnit\Framework\TestCase;
use Whoops\{Run, RunInterface};
use Whoops\Handler\HandlerInterface;
use BitFrame\Whoops\Handler\JsonpResponseHandler;
use Throwable;
use RuntimeException;
use InvalidArgumentException;

use function json_encode;
use function json_decode;
use function substr;
use function strpos;
use function trim;
use function get_class;
use function reset;

use const JSON_HEX_TAG;
use const JSON_HEX_APOS;
use const JSON_HEX_QUOT;
use const JSON_HEX_AMP;
use const JSON_UNESCAPED_SLASHES;

/**
 * @covers \BitFrame\Whoops\Handler\JsonpResponseHandler
 */
class JsonpResponseHandlerTest extends TestCase
{
    /** @var string */
    private const CALLBACK = 'foo';

    private HandlerInterface $handler;

    private RunInterface $whoops;

    public function setUp(): void
    {
        $this->handler = new JsonpResponseHandler(self::CALLBACK);

        $this->whoops = new Run();
        $this->whoops->allowQuit(false);
        $this->whoops->writeToOutput(false);
        $this->whoops->pushHandler($this->handler);
        $this->whoops->register();
    }

    public function testReturnsWithoutFrames(): void
    {
        $this->handler->setJsonApi(false);
        $this->handler->addTraceToOutput(false);

        try {
            throw new RuntimeException('foobar');
        } catch (Throwable $e) {
            $output = $this->whoops->handleException($e);
            $jsonp = $this->decodeJsonp($output)[self::CALLBACK];

            $this->assertArrayHasKey('error', $jsonp);
            $this->assertArrayHasKey('type', $jsonp['error']);
            $this->assertArrayHasKey('file', $jsonp['error']);
            $this->assertArrayHasKey('line', $jsonp['error']);

            $this->assertEquals(__FILE__, $jsonp['error']['file']);
            $this->assertEquals('foobar', $jsonp['error']['message']);
            $this->assertEquals(get_class($e), $jsonp['error']['type']);

            $this->assertArrayNotHasKey('trace', $jsonp['error']);
        }
    }

    public function testReturnsWithFrames(): void
    {
        $this->handler->setJsonApi(false);
        $this->handler->addTraceToOutput(true);

        try {
            throw new RuntimeException('foobar');
        } catch (Throwable $e) {
            $output = $this->whoops->handleException($e);
            $jsonp = $this->decodeJsonp($output)[self::CALLBACK];

            $this->assertArrayHasKey('trace', $jsonp['error']);

            $traceFrame = reset($jsonp['error']['trace']);
            $this->assertArrayHasKey('file', $traceFrame);
            $this->assertArrayHasKey('line', $traceFrame);
            $this->assertArrayHasKey('function', $traceFrame);
            $this->assertArrayHasKey('class', $traceFrame);
            $this->assertArrayHasKey('args', $traceFrame);
        }
    }

    public function testReturnsJsonApi(): void
    {
        $this->handler->setJsonApi(true);
        $this->handler->addTraceToOutput(false);

        try {
            throw new RuntimeException('foobar');
        } catch (Throwable $e) {
            $output = $this->whoops->handleException($e);
            $jsonp = $this->decodeJsonp($output)[self::CALLBACK];

            $this->assertArrayHasKey('errors', $jsonp);
            $this->assertArrayHasKey('type', $jsonp['errors'][0]);
            $this->assertArrayHasKey('file', $jsonp['errors'][0]);
            $this->assertArrayHasKey('line', $jsonp['errors'][0]);

            $this->assertEquals(__FILE__, $jsonp['errors'][0]['file']);
            $this->assertEquals('foobar', $jsonp['errors'][0]['message']);
            $this->assertEquals(get_class($e), $jsonp['errors'][0]['type']);

            $this->assertArrayNotHasKey('trace', $jsonp['errors']);
        }
    }

    public function invalidCallbackProvider(): array
    {
        return [
            ['\u200C\u200D'], ['+invalid'], ['true'], ['false'],
            ['instanceof'], ['break'], ['do'], ['instanceof'],
            ['typeof'], ['case'], ['else'], ['new'],
            ['var'], ['catch'], ['finally'], ['return'],
            ['void'], ['continue'], ['for'], ['switch'],
            ['while'], ['debugger'], ['function'], ['this'],
            ['with'], ['default'], ['if'], ['throw'],
            ['delete'], ['in'], ['try'], ['class'],
            ['enum'], ['extends'], ['super'], ['const'],
            ['export'], ['import'], ['implements'], ['let'],
            ['private'], ['public'], ['yield'], ['interface'],
            ['package'], ['protected'], ['static'], ['null'],
        ];
    }

    /**
     * @dataProvider invalidCallbackProvider
     *
     * @param mixed $callbackName
     */
    public function testThrowsExceptionWhenCallbackIsInvalid($callbackName): void
    {
        $this->expectException(InvalidArgumentException::class);
        new JsonpResponseHandler($callbackName);
    }

    public function valuesToJsonEncodeProvider(): array
    {
        return [
            'uri' => [
                'https://example.com/foo?bar=baz&baz=bat',
                'https://example.com/foo?bar=baz\u0026baz=bat'
            ],
            'html' => [
                '<p class="test">content</p>',
                '\u003Cp class=\u0022test\u0022\u003Econtent\u003C/p\u003E'
            ],
            'string' => ["Don't quote!", 'Don\u0027t quote!'],
        ];
    }

    /**
     * @runInSeparateProcess
     * @dataProvider valuesToJsonEncodeProvider
     *
     * @param string $value
     */
    public function testUsesSaneDefaultJsonEncodingFlags($value): void
    {
        $defaultFlags = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES;
        $handler = new JsonpResponseHandler(self::CALLBACK);
        $handler->setEncoding($defaultFlags);
        $this->whoops->pushHandler($handler);

        $expected = json_encode($value, $defaultFlags);

        try {
            throw new RuntimeException($value);
        } catch (Throwable $e) {
            $output = $this->whoops->handleException($e);

            $this->assertStringContainsString($expected, $output);
        }
    }

    public function testJsonEncodeFlags(): void
    {
        try {
            throw new RuntimeException('<>\'&"');
        } catch (Throwable $e) {
            $output = $this->whoops->handleException($e);

            $this->assertStringContainsString('"\u003C\u003E\u0027\u0026\u0022"', $output);
        }
    }

    private function decodeJsonp(string $jsonp): array
    {
        $callback = '';

        if($jsonp[0] !== '[' && $jsonp[0] !== '{') {
            $jsonStart = strpos($jsonp, '(');
            $callback = substr($jsonp, 0, $jsonStart);
            $jsonp = substr($jsonp, $jsonStart);
        }

        $jsonp = trim($jsonp);
        $jsonp = trim($jsonp,'()');

        return [$callback => json_decode($jsonp, true) ?: []];
    }
}
