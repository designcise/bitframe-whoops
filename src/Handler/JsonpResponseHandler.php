<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2023 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Handler;

use Whoops\Handler\Handler;
use Whoops\Exception\Formatter;
use InvalidArgumentException;

use function json_encode;
use function explode;
use function preg_match;
use function in_array;

use const JSON_THROW_ON_ERROR;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;
use const JSON_HEX_QUOT;
use const JSON_HEX_TAG;
use const JSON_HEX_AMP;
use const JSON_HEX_APOS;
use const JSON_UNESCAPED_SLASHES;

/**
 * Catches an exception and converts it to a JSONP response.
 * Can also return exception frames for consumption by an API.
 */
class JsonpResponseHandler extends Handler
{
    /** @var string */
    private const MIME = 'application/json';

    /** @var int */
    private const DEFAULT_ENCODING = JSON_PARTIAL_OUTPUT_ON_ERROR
        | JSON_THROW_ON_ERROR
        | JSON_HEX_QUOT
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_UNESCAPED_SLASHES;

    private bool $returnFrames = false;

    private bool $jsonApi = false;

    public function __construct(
        private string $callback,
        private int $encodingOptions = self::DEFAULT_ENCODING,
    ) {
        if (! $this->isCallbackValid($callback)) {
            throw new InvalidArgumentException('Callback name is invalid');
        }
    }

    public function addTraceToOutput(bool $returnFrames): self
    {
        $this->returnFrames = $returnFrames;
        return $this;
    }

    public function handle(): int
    {
        $error = Formatter::formatExceptionAsDataArray(
            $this->getInspector(),
            $this->returnFrames
        );

        $response = ($this->jsonApi) ? ['errors' => [$error]] : ['error' => $error];

        $json = json_encode($response, $this->encodingOptions);
        echo "{$this->callback}($json)";

        return Handler::QUIT;
    }

    public function setJsonApi(bool $jsonApi): self
    {
        $this->jsonApi = $jsonApi;
        return $this;
    }

    public function setEncoding(int $encoding): self
    {
        $this->encodingOptions = $encoding;
        return $this;
    }

    public function contentType(): string
    {
        return self::MIME;
    }

    /**
     * @param string $callback
     *
     * @return bool
     *
     * @see \Symfony\Component\HttpFoundation\JsonResponse::setCallback()
     */
    private function isCallbackValid(string $callback): bool
    {
        $pattern = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*(?:\[(?:"(?:\\\.|[^"\\\])*"|\'(?:\\\.|[^\'\\\])*\'|\d+)\])*?$/u';

        $reserved = [
            'break', 'do', 'instanceof', 'typeof', 'case', 'else', 'new', 'var', 'catch', 'finally',
            'return','void', 'continue', 'for', 'switch', 'while', 'debugger', 'function', 'this',
            'with', 'default', 'if', 'throw', 'delete', 'in', 'try', 'class', 'enum', 'extends', 'super',
            'const', 'export', 'import', 'implements', 'let', 'private', 'public', 'yield', 'interface',
            'package', 'protected', 'static', 'null', 'true', 'false',
        ];

        $parts = explode('.', $callback);

        foreach ($parts as $part) {
            if (! preg_match($pattern, $part) || in_array($part, $reserved, true)) {
                return false;
            }
        }

        return true;
    }
}
