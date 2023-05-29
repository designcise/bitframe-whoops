<?php

declare(strict_types=1);

namespace BitFrame\Whoops\Enum;

enum HandlerProviderType: string
{
    /** @var string */
    final public const HTML = 'html';
    final public const JSON = 'json';
    final public const JSONP = 'jsonp';
    final public const TEXT = 'text';
    final public const XML = 'xml';
}
