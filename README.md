# BitFrame\ErrorHandler\Whoops

Whoops error handler middleware to handle application or middleware specific errors.

### Installation

See [installation docs](https://www.bitframephp.com/middleware/error-handler/whoops) for instructions on installing and using this middleware.

### Usage Example

```
use \BitFrame\ErrorHandler\WhoopsErrorHandler;

require 'vendor/autoload.php';

$app = new \BitFrame\Application;

$format = 'auto';
$handler = new WhoopsErrorHandler($format);

$app->run([
    /* In order to output response from the whoops error handler, 
     * make sure you include a response emitter middleware, for example:
     * \BitFrame\Message\DiactorosResponseEmitter::class, */
    $handler
]);
```

### Tests

To execute the test suite, you will need [PHPUnit](https://phpunit.de/).

### Contributing

* File issues at https://github.com/designcise/bitframe-whoops/issues
* Issue patches to https://github.com/designcise/bitframe-whoops/pulls

### Documentation

Documentation is available at:

* https://www.bitframephp.com/middleware/error-handler/whoops/

### License

Please see [License File](LICENSE.md) for licensing information.