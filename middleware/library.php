<?php
// stratigility middleware "library"

// class needed
use Zend\Diactoros\Response;

// init constants
define('LOG_FILE', __DIR__ . '/../logs/access.log');

// order in which middleware pages should be attached to the pipe
$order = ['log','city','postcode','error'];

// database credentials
$dsn  = 'mysql:host=localhost;dbname=geonames';
$user = 'test';
$pass = 'password';
$opts = [PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION];

// create database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $opts);
} catch (Throwable $e) {
    error_log(get_class($e) . ':' . $e->getMessage());
    die(json_encode(['error' => 'Database error']);
}

$middleware = [
    // middleware: writes to a log file; does not return a response
    'log' => [
        'path' => FALSE,
        'func' => function ($req, $handler) {
            $text = sprintf('%20s : %10s : %16s : %s' . PHP_EOL,
                            date('Y-m-d H:i:s'),
                            $req->getUri()->getPath(),
                            ($req->getHeaders()['accept'][0] ?? 'N/A'),
                            ($req->getServerParams()['REMOTE_ADDR']) ?? 'Command Line');
            file_put_contents(LOG_FILE, $text, FILE_APPEND);
            return $handler->handle($req);
        }
    ],
    // middleware: looks up post code data by city; returns a JSON response
    'city' => [
        'path' => '/city',
        'func' => function ($req, $handler) {
            $path = $req->getUri()->getPath();
            $path = preg_replace('/[^A-Za-z]/', '', $path);
            $response = new Response();
            // success
            $response->withStatus(200, 'OK')->getBody()->write(json_encode($path));
            return $response;
        }
    ],
    // middleware: looks up post code data by post code; returns a JSON response
    'postcode' => [
        'path' => '/postcode',
        'func' => function ($req, $handler) {
            $path = $req->getUri()->getPath();
            $path = preg_replace('/[^A-Za-z]/', '', $path);
            $response = new Response();
            // success
            $response->withStatus(200, 'OK')->getBody()->write(json_encode($path));
            return $response;
        }
    ],
    // middleware: returns a JSON error response
    'error' => [
        'path' => '/error',
        'func' => function ($req, $handler) {
            $response = (new Response())->withStatus(400, 'Bad Request');
            $error = [
                'error'  => 'An unknown error has occurred',
                'usage'  => 'If you enter /city/[A-Z]* as a URL, app does an SELECT ... WHERE post_code.place_name LIKE XXX%' . PHP_EOL
                            . 'If you enter /postcode/[A-Z0-9]* as a URL, app does an SELECT ... WHERE post_code.postal_code LIKE XXX%' . PHP_EOL
            $response->getBody()->write(json_encode($error);
            return $response;
        }
    ]
];

