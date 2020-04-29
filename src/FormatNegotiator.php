<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops;

use Psr\Http\Message\ServerRequestInterface;

use BitFrame\Whoops\Provider\{
    ProviderInterface,
    HtmlHandlerProvider,
    JsonHandlerProvider,
    TextHandlerProvider,
    XmlHandlerProvider
};

use function asort;
use function array_key_last;

/**
 * Detect any of the supported preferred formats
 * from an HTTP request.
 */
class FormatNegotiator
{
    private static array $formats = [
        HtmlHandlerProvider::class,
        JsonHandlerProvider::class,
        TextHandlerProvider::class,
        XmlHandlerProvider::class,
    ];

    public static function fromRequest(ServerRequestInterface $request): ProviderInterface
    {
        $acceptTypes = $request->getHeader('accept');

        if (! isset($acceptTypes[0])) {
            return new HtmlHandlerProvider();
        }

        $acceptType = $acceptTypes[0];
        $score = self::calculateRelevance($acceptType);
        asort($score);

        $format = array_key_last($score);

        return ($score[$format] === 0)
            ? new HtmlHandlerProvider()
            : new $format();
    }

    private static function calculateRelevance(string $acceptType): array
    {
        $score = [];
        foreach (self::$formats as $format) {
            foreach ($format::MIMES as $value) {
                $score[$format] = $score[$format] ?? 0;
                $score[$format] += (int) (strpos($acceptType, $value) !== false);
            }
        }
        return $score;
    }
}
