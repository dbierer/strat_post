<?php
// TO RUN THIS DEMO:
/*
 * 1. Run "composer update" to install stratigility, etc.
 * 2. Create database `geonames`
 * 3. Run ../data/install_db.php to download source data and populate `geoname` table
 * 4. From this directory run this command: "php -S localhost:9999 -t public"
 * 5. From your browser enter this URL: "http://localhost:9999"
 */

// autoloader
require __DIR__ . '/../vendor/autoload.php';

// main classes needed
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\NotFoundHandler;
use Laminas\Stratigility\MiddlewarePipe;

// Load the middleware
$middleware = require __DIR__ . '/../middleware/library.php';

// NOTE: these are *functions* which provide convient wrappers:
//       "middleware()" produces middleware from anonymous functions
//       "path()" addes routing and requires that you call "middleware()" as 2nd argument
use function Laminas\Stratigility\middleware;
use function Laminas\Stratigility\path;

try {

    // set up the pipeline and server
    $pipeline = new MiddlewarePipe();

    // attach middleware to the pipe in $order
    // NOTE the use of a linked list: $order is linked to $middleware
    foreach ($key as $val)
        $pipeline->pipe(path($val['path'], middleware($val['func'])));

    $pipeline->pipe(new NotFoundHandler(function () { return new Response(); }));
    $server = new RequestHandlerRunner(
        $pipeline,
        new SapiEmitter(),
        static function () {
            return ServerRequestFactory::fromGlobals();
        },
        static function (\Throwable $e) {
            $message = ['error' => ['file' => __FILE__, 'class' => get_class($e), 'message' => $e->getMessage()]];
            error_log(__FILE__ . ':' . json_encode($message));
            $response = (new ResponseFactory())->createResponse(500);
            $response->getBody()->write('Internal Error');
            return $response;
        }
    );
    $server->run();

} catch (Throwable $e) {

    $message = ['error' => ['file' => __FILE__, 'class' => get_class($e), 'message' => $e->getMessage()]];
    error_log(__FILE__ . ':' . json_encode($message));
    $response = (new ResponseFactory())->createResponse(500);
    $response->getBody()->write('Internal Error');
    return $response;

}
