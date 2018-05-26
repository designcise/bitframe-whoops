<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @author    Filipe Dobreira
 * @copyright Copyright (c) 2013-2018 Filipe Dobreira (http://github.com/filp)
 *
 * @license   https://github.com/designcise/bitframe-whoops/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\ErrorHandler\Handler;

use \Whoops\Handler\Handler;
use \Whoops\Exception\Formatter;

/**
 * Catches an exception and converts it to a JSONP
 * response. Additionally can also return exception
 * frames for consumption by an API. Based on
 * \Whoops\Handler\JsonResponseHandler.
 */
class JsonpResponseHandler extends Handler
{
    /** @var bool */
    private $returnFrames = false;

    /** @var bool */
    private $jsonApi = false;
    
    /** @var string */
    private $callback;

    /**
     * @param string $callback JSONP callback
     */
    public function __construct(string $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Returns errors[[]] instead of error[] to be in 
     * compliance with the json:api spec
     *
     * @param bool $jsonApi Default is false
     *
     * @return $this
     */
    public function setJsonApi(bool $jsonApi = false): self
    {
        $this->jsonApi = (bool) $jsonApi;
        return $this;
    }

    /**
     * @param bool|null $returnFrames
     *
     * @return bool|$this
     */
    public function addTraceToOutput(?bool $returnFrames = null)
    {
        if (func_num_args() == 0) {
            return $this->returnFrames;
        }

        $this->returnFrames = (bool) $returnFrames;
        return $this;
    }

    /**
     * Handle errors.
     *
     * @return int
     */
    public function handle(): int
    {
        if ($this->jsonApi === true) {
            $response = [
                'errors' => [
                    Formatter::formatExceptionAsDataArray(
                        $this->getInspector(),
                        $this->addTraceToOutput()
                    ),
                ]
            ];
        } else {
            $response = [
                'error' => Formatter::formatExceptionAsDataArray(
                    $this->getInspector(),
                    $this->addTraceToOutput()
                ),
            ];
        }

        $json = json_encode($response, defined('JSON_PARTIAL_OUTPUT_ON_ERROR') ? JSON_PARTIAL_OUTPUT_ON_ERROR : 0);
        echo "{$this->callback}($json)";

        return Handler::QUIT;
    }

    /**
     * Get content type.
     * 
     * @return string
     */
    public function contentType(): string
    {
        return 'application/json';
    }
}
