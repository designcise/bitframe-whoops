<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Handler;

use Whoops\Handler\Handler;
use Whoops\Exception\Formatter;

use function json_encode;

use const JSON_THROW_ON_ERROR;
use const JSON_PARTIAL_OUTPUT_ON_ERROR;

/**
 * Catches an exception and converts it to a JSONP
 * response. Additionally can also return exception
 * frames for consumption by an API.
 */
class JsonpResponseHandler extends Handler
{
    private const MIME = 'application/json';

    private bool $returnFrames = false;

    private bool $jsonApi = false;

    private string $callback;

    public function __construct(string $callback)
    {
        $this->callback = $callback;
    }

    public function addTraceToOutput(bool $returnFrames): self
    {
        $this->returnFrames = $returnFrames;
        return $this;
    }

    /**
     * @return int
     *
     * @throws \JsonException
     */
    public function handle(): int
    {
        if ($this->jsonApi === true) {
            $response = [
                'errors' => [
                    Formatter::formatExceptionAsDataArray(
                        $this->getInspector(),
                        $this->returnFrames
                    ),
                ]
            ];
        } else {
            $response = [
                'error' => Formatter::formatExceptionAsDataArray(
                    $this->getInspector(),
                    $this->returnFrames
                ),
            ];
        }

        $json = json_encode($response, JSON_THROW_ON_ERROR | JSON_PARTIAL_OUTPUT_ON_ERROR);
        echo "{$this->callback}($json)";

        return Handler::QUIT;
    }

    public function setJsonApi(bool $jsonApi): self
    {
        $this->jsonApi = $jsonApi;
        return $this;
    }

    public function getContentType(): string
    {
        return self::MIME;
    }
}
