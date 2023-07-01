<?php

/**
 * BitFrame Framework (https://www.bitframephp.com)
 *
 * @author    Daniyal Hamid
 * @copyright Copyright (c) 2017-2023 Daniyal Hamid (https://designcise.com)
 * @license   https://bitframephp.com/about/license MIT License
 */

declare(strict_types=1);

namespace BitFrame\Whoops\Provider;

use BitFrame\Whoops\Enum\HandlerProviderType;
use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;
use Whoops\Handler\HandlerInterface;

use function is_a;
use function asort;
use function array_key_last;
use function str_contains;

/**
 * Detect any of the supported preferred formats from an
 * HTTP request.
 */
class HandlerProviderNegotiator implements ProviderInterface
{
    private array $handlerProviders = [
        HandlerProviderType::HTML => HtmlHandlerProvider::class,
        HandlerProviderType::JSON => JsonHandlerProvider::class,
        HandlerProviderType::JSONP => JsonpHandlerProvider::class,
        HandlerProviderType::TEXT => TextHandlerProvider::class,
        HandlerProviderType::XML => XmlHandlerProvider::class,
    ];

    private ?ProviderInterface $activeProvider = null;

    public function add(string $type, string $provider): void
    {
        if (! is_a($provider, ProviderInterface::class, true)) {
            throw new InvalidArgumentException(
                'Handler provider must be instance of ' . ProviderInterface::class
            );
        }

        $this->handlerProviders[$type] = $provider;
    }

    public function getHandler(ServerRequestInterface $request): HandlerInterface
    {
        return $this->getPreferredProvider($request)->getHandler($request);
    }

    public function getPreferredContentType(ServerRequestInterface $request): string
    {
        return $this->getPreferredProvider($request)->getPreferredContentType($request);
    }

    public function getPreferredProvider(ServerRequestInterface $request): ProviderInterface
    {
        if ($this->activeProvider instanceof ProviderInterface) {
            return $this->activeProvider;
        }

        $acceptTypes = $request->getHeader('accept');
        $default = $this->handlerProviders[HandlerProviderType::HTML];

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
                $score[$handlerProvider] += (int) (str_contains($acceptType, $value));
            }
        }
        return $score;
    }
}
