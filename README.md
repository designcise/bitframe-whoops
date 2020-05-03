# BitFrame\Whoops

[![codecov](https://codecov.io/gh/designcise/bitframe-whoops/branch/2.x/graph/badge.svg)](https://codecov.io/gh/designcise/bitframe-whoops)
[![Build Status](https://travis-ci.org/designcise/bitframe-whoops.svg?branch=2.x)](https://travis-ci.org/designcise/bitframe-whoops)

Whoops error handler middleware to handle application or middleware specific errors.

## Installation

```
$ composer require "designcise/bitframe-whoops:2.x-dev"
```

Please note that this package requires PHP 7.4.0 or newer.

## Quickstart

### Instantiating

The constructor has the following signature:

```php
new ErrorHandler(\Psr\Http\Message\ResponseFactoryInterface, [options]);
```

1. The first argument to the constructor must be an instance of `ResponseFactoryInterface`;
1. The second argument to the constructor is an optional array of options. You can configure the following options:
    1. `catchGlobalErrors`: When set to `true` errors will be handled outside of current batch of middleware set.
    1. Other options are simply method names in `Whoops\Handler\*Handler.php` and `BitFrame\Whoops\Handler\*Handler.php`. For example, to set `Whoops\Handler\JsonResponseHandler::setJsonApi()` you would pass in: `['setJsonApi' => false]`, etc.

### How to Run the Middleware

To run the middleware, simply pass in a `BitFrame\Whoops\ErrorHandler` instance to your middleware runner / dispatcher.

For example, to handle middleware-specific errors with `BitFrame\App` (or other PSR-15 dispatchers) it would look something like this:

```php
use BitFrame\App;
use BitFrame\Emitter\SapiEmitter;
use BitFrame\Whoops\ErrorHandler;
use BitFrame\Factory\HttpFactory;

$app = new App();

$middleware = function () {
    throw new \Exception('hello world!');
};

$app->use([
    SapiEmitter::class,
    new ErrorHandler(HttpFactory::getFactory(), [
        'addTraceToOutput' => true,
        'setJsonApi' => false,
    ]),
    $middleware,
]);

$app->run();
```

To handle global errors with `BitFrame\App` (or other PSR-15 dispatchers) it would look something like this:

```php
use BitFrame\App;
use BitFrame\Whoops\ErrorHandler;
use BitFrame\Factory\HttpFactory;

$app = new App();

$app->run([
    new ErrorHandler(HttpFactory::getFactory(), [
        'catchGlobalErrors' => true,
        'addTraceToOutput' => true,
        'setJsonApi' => false,
    ]),
]);

throw new \Exception('hello world!');
```

### How Does It Work?

The error handler middleware automatically determines the error handler to use based on the `Accept` header. The following error handler provders are included:

1. `BitFrame\Whoops\Provider\HtmlHandlerProvider` for `Whoops\Handler\PrettyPageHandler`;
1. `BitFrame\Whoops\Provider\JsonHandlerProvider` for `Whoops\Handler\JsonResponseHandler`;
1. `BitFrame\Whoops\Provider\JsonpHandlerProvider` for `BitFrame\Whoops\Handler\JsonpResponseHandler`;
1. `BitFrame\Whoops\Provider\TextHandlerProvider` for `Whoops\Handler\PlainTextHandler`;
1. `BitFrame\Whoops\Provider\XmlHandlerProvider` for `Whoops\Handler\XmlResponseHandler`;

## Tests

To run the tests you can use the following commands:

| Command          | Type            |
| ---------------- |:---------------:|
| `composer test`  | PHPUnit tests   |
| `composer style` | CodeSniffer     |
| `composer md`    | MessDetector    |
| `composer check` | PHPStan         |

## Contributing

* File issues at https://github.com/designcise/bitframe-whoops/issues
* Issue patches to https://github.com/designcise/bitframe-whoops/pulls

## Documentation

Complete documentation for v2.0 will be available soon.

## License

Please see [License File](LICENSE.md) for licensing information.