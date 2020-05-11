<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2020 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

namespace BitFrame\Whoops\Provider;

use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;

use Whoops\Handler\HandlerInterface;
use function is_a;
use function asort;
use function array_key_last;
use function strpos;

/**
 * Detect any of the supported preferred formats from an
 * HTTP request.
 */
class HandlerProviderNegotiator extends AbstractProvider
{
    /** @var string */
    public const HTML = 'html';

    /** @var string */
    public const JSON = 'json';

    /** @var string */
    public const JSONP = 'jsonp';

    /** @var string */
    public const TEXT = 'text';

    /** @var string */
    public const XML = 'xml';

    private array $handlerProviders = [
        self::HTML => HtmlHandlerProvider::class,
        self::JSON => JsonHandlerProvider::class,
        self::JSONP => JsonpHandlerProvider::class,
        self::TEXT => TextHandlerProvider::class,
        self::XML => XmlHandlerProvider::class,
    ];

    private ?AbstractProvider $activeProvider = null;

    public function add(string $type, string $provider): void
    {
        if (! is_a($provider, AbstractProvider::class, true)) {
            throw new InvalidArgumentException(
                'Handler provider must be instance of ' . AbstractProvider::class
            );
        }

        $this->handlerProviders[$type] = $provider;
    }

    public function getHandler(): HandlerInterface
    {
        return $this->getPreferredProvider()->getHandler();
    }

    public function getPreferredContentType(): string
    {
        return $this->getPreferredProvider()->getPreferredContentType();
    }

    public function getPreferredProvider(): AbstractProvider
    {
        if ($this->activeProvider instanceof AbstractProvider) {
            return $this->activeProvider;
        }

        $request = $this->getRequest();
        $acceptTypes = $request->getHeader('accept');
        $default = $this->handlerProviders[self::HTML];

        if (! isset($acceptTypes[0])) {
            $this->activeProvider = new $default($request);
            return $this->activeProvider;
        }

        $acceptType = $acceptTypes[0];
        $score = $this->calculateRelevance($acceptType);
        asort($score);

        $provider = array_key_last($score);

        $this->activeProvider = ($score[$provider] === 0)
            ? new $default($request)
            : new $provider($request);

        return $this->activeProvider;
    }

    private function calculateRelevance(string $acceptType): array
    {
        $score = [];
        foreach ($this->handlerProviders as $handlerProvider) {
            foreach ($handlerProvider::MIMES as $value) {
                $score[$handlerProvider] = $score[$handlerProvider] ?? 0;
                $score[$handlerProvider] += (int) (strpos($acceptType, $value) !== false);
            }
        }
        return $score;
    }
}
