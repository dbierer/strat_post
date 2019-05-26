<?php
// TO RUN THIS DEMO:
/*
 * 1. Run "composer update" to install stratigility, etc.
 * 2. Create database `geonames`
 * 3. Run ../data/install_db.php to download source data and populate `geoname` table
 * 4. From this directory run this command: "php -S localhost:9999 -t public"
 * 5. From your browser enter this URL: "http://localhost:9999"
 */

// env
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../middleware/library.php';

// main classes needed
use Zend\Diactoros\Response;
use Zend\Diactoros\Server;
use Zend\Stratigility\Middleware\NotFoundHandler;
use Zend\Stratigility\MiddlewarePipe;

// NOTE: these are *functions* which provide convient wrappers:
//       "middleware()" produces middleware from anonymous functions
//       "path()" addes routing and requires that you call "middleware()" as 2nd argument
use function Zend\Stratigility\middleware;
use function Zend\Stratigility\path;

// set up the pipe and server
$app = new MiddlewarePipe();
$server = Server::createServer([$app, 'handle'], $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

// attach middleware to the pipe in $order
// NOTE the use of a linked list: $order is linked to $middleware
foreach ($order as $key) {
    if (isset($middleware[$key]['path'])) {
        $app->pipe(path($middleware[$key]['path'], middleware($middleware[$key]['func'])));
    } else {
        $app->pipe(middleware($middleware[$key]['func']));
    }
}

// 404 handler
$app->pipe(new NotFoundHandler(function () {
    return new Response();
}));

// end of the pipe
$server->listen(function ($req, $res) {
    return $res;
});
