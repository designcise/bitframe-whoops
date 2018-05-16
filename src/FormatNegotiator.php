<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2018 Daniyal Hamid (https://designcise.com)
 *
 * @author    Franz Liedke
 * @copyright Copyright (c) 2015-2017 Franz Liedke
 *
 * @license   https://github.com/designcise/bitframe/blob/master/LICENSE.md MIT License
 */

namespace BitFrame\ErrorHandler;

use \Psr\Http\Message\ServerRequestInterface;

/**
 * Detect any of the supported preferred formats 
 * from an HTTP request.
 */
class FormatNegotiator
{
    /** @var array Available formats with MIME types */
    private static $formats = [
        'html' => ['text/html', 'application/xhtml+xml'],
        'json' => ['application/json', 'text/json', 'application/x-json'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'txt' => ['text/plain']
    ];

    /**
     * Returns the preferred format based on the Accept header.
     *
     * @param ServerRequestInterface $request
     * @return string
     */
    public static function getPreferredFormat(ServerRequestInterface $request): string
    {
        $acceptTypes = $request->getHeader('accept');

        if (count($acceptTypes) > 0) {
            $acceptType = $acceptTypes[0];

            // as many formats may match for a given Accept header, 
            // look for the one that fits the best
            $counters = [];
            foreach (self::$formats as $format => $values) {
                foreach ($values as $value) {
                    $counters[$format] = isset($counters[$format]) ? $counters[$format] : 0;
                    $counters[$format] += intval(strpos($acceptType, $value) !== FALSE);
                }
            }

            // sort the array to retrieve the format that best matches the Accept header
            asort($counters);
            end($counters);
            return key($counters);
        }

        return 'html';
    }
}
