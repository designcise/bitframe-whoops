<?php

declare(strict_types=1);

namespace BitFrame\Whoops\Enum;

// @TODO when using enums as array keys is allowed, this should be refactored to use `case` syntax
enum HandlerProviderType: string
{
    final public const HTML = 'html';
    final public const JSON = 'json';
    final public const JSONP = 'jsonp';
    final public const TEXT = 'text';
    final public const XML = 'xml';
}
