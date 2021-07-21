# BitFrame\Whoops

[![codecov](https://codecov.io/gh/designcise/bitframe-whoops/branch/master/graph/badge.svg)](https://codecov.io/gh/designcise/bitframe-whoops)
[![Build Status](https://travis-ci.com/designcise/bitframe-whoops.svg?branch=master)](https://travis-ci.com/designcise/bitframe-whoops)

Whoops error handler middleware to handle application or middleware specific errors.

## Installation

```
$ composer require "designcise/bitframe-whoops"
```

Please note that this package requires PHP 8.0 or newer.

## Quickstart

### Instantiating

The constructor has the following signature:

```php
new ErrorHandler(
    \Psr\Http\Message\ResponseFactoryInterface,
    \BitFrame\Whoops\Provider\HandlerProviderNegotiator::class
    [options]
);
```

1. The first argument to the constructor must be an instance of `Psr\Http\Message\ResponseFactoryInterface`;
1. The second argument to the constructor must be an implementation of `\BitFrame\Whoops\Provider\ProviderInterface`;
1. The third argument to the constructor is an optional array of options to specify the following:
    1. `catchGlobalErrors`: When set to `true` errors will be handled outside of current batch of middleware set.
    1. Other options are simply method names in `Whoops\Handler\*Handler.php` and `BitFrame\Whoops\Handler\*Handler.php`. For example, to set `Whoops\Handler\JsonResponseHandler::setJsonApi()` you would pass in: `['setJsonApi' => false]`, etc.

As a shortcut, you can also use the static method `ErrorHandler::fromNegotiator($factory, $options)`. This would use the `\BitFrame\Whoops\Provider\HandlerProviderNegotiator` by default.

### How to Run the Middleware

To run the middleware, simply pass in a `BitFrame\Whoops\ErrorHandler` instance to your middleware runner / dispatcher.

For example, to handle middleware-specific errors with `BitFrame\App` (or other PSR-15 dispatchers) it would look something like this:

```php
use BitFrame\App;
use BitFrame\Emitter\SapiEmitter;
use BitFrame\Whoops\ErrorHandler;
use \BitFrame\Whoops\Provider\HandlerProviderNegotiator;
use BitFrame\Factory\HttpFactory;

$app = new App();

$middleware = function () {
    throw new \Exception('hello world!');
};

$app->use([
    SapiEmitter::class,
    new ErrorHandler(HttpFactory::getFactory(), HandlerProviderNegotiator::class, [
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
    ErrorHandler::fromNegotiator(HttpFactory::getFactory(), [
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

Complete documentation for v3 will be available soon.

## License

Please see [License File](LICENSE.md) for licensing information.