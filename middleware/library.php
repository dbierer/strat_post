<?php
// stratigility middleware "library"

// class needed
use Zend\Diactoros\Response;

// init constants
define('LOG_FILE', __DIR__ . '/../logs/access.log');

// order in which middleware pages should be attached to the pipe
$order = ['log','api','home','error'];

// database credentials
$dsn  = 'mysql:host=localhost;dbname=geonames';
$user = 'test';
$pass = 'password';
$opts = [PDO::ATTR_ERRMODE=> PDO::ERRMODE_EXCEPTION];

// message
$usage = [
    0 => 'Info returned: array of post codes + city, state/province, latitude and longitude',
    1 => 'URL: "/api/?city=[A-Z]*" does lookup based on city',
    2 => 'URL: "/api/?postcode=[A-Z0-9]*" does lookup post code',
    3 => 'URL: "... &country=[A-Z]{2}" add this to either of the URLs above to further qualify based on country code',
];

// field mappings
$fields = [
    'id'              => 'post_code_id',
    'country'         => 'country_code',
    'postcode'        => 'postal_code',
    'city'            => 'place_name',
    'state_prov_name' => 'admin_name1',
    'state_prov_code' => 'admin_code1',
    'locality_name'   => 'admin_name2',
    'locality_code'   => 'admin_code2',
    'region_name'     => 'admin_name3',
    'region_code'     => 'admin_code3',
    'latitude'        => 'latitude',
    'longitude'       => 'longitude',
    'accuracy'        => 'accuracy'
];

// create database connection
try {
    $pdo = new PDO($dsn, $user, $pass, $opts);
} catch (Throwable $e) {
    error_log(get_class($e) . ':' . $e->getMessage());
    die(json_encode(['error' => 'Database error']));
}

$response = new Response();
header('Content-Type: application/json');

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
    // middleware: looks up post code data and returns a JSON response
    'api' => [
        'path' => '/api',
        'func' => function ($req, $handler)  use ($response, $pdo, $fields) {
            try {
                // grab query params
                $input = $req->getQueryParams();
                // formulate SQL
                $sql = 'SELECT * FROM post_code ';
                if (isset($input['city'])) {
                    $sql .= 'WHERE ' . $fields['city'] . ' LIKE ?';
                    $params[] = $input['city'] . '%';
                } elseif (isset($input['postcode'])) {
                    $sql .= 'WHERE ' . $fields['postcode'] . ' LIKE ?';
                    $params[] = $input['postcode'] . '%';
                } else {
                    // if "city" or "postcode" params not present, go to next middleware
                    return $handler->handle($req);
                }
                // further qualifers
                if (isset($input['country'])) {
                    $sql .= ' AND ' . $fields['country'] . ' = ?';
                    $params[] = $input['country'];
                }
                // success
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                // log SQL
                $text = sprintf('%20s : %s' . PHP_EOL,
                                date('Y-m-d H:i:s'), $sql);
                file_put_contents(LOG_FILE, $text, FILE_APPEND);
                // rewrite results mapping for-display fields instead of revealing database column names
                while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $data['data'][] = array_combine(array_keys($fields), array_values($item));
                }
                $response->withStatus(200, 'OK')->getBody()->write(json_encode($data));
            } catch (Throwable $e) {
                $error = ['error' => ['file' => __FILE__, 'class' => get_class($e), 'message' => $e->getMessage()]];
                $response->withStatus(500, 'Internal Server Error')->getBody()->write(json_encode($error));
            }
            return $response;
        }
    ],
    // middleware: returns a JSON response with usage information
    'home' => [
        'path' => '/',
        'func' => function ($req, $handler) use ($response, $usage) {
            // success
            $message = [
                'usage'  => $usage,
                'request' => get_class($req),
                'response' => get_class($response)
            ];
            $response->withStatus(200, 'OK')->getBody()->write(json_encode($message));
            return $response;
        }
    ],
    // middleware: returns a JSON error response
    'error' => [
        'path' => '/error',
        'func' => function ($req, $handler)  use ($response, $usage) {
            $error = [
                'error'  => 'An unknown error has occurred',
                'usage'  => $usage,
            ];
            $response->withStatus(400, 'Bad Request')->getBody()->write(json_encode($error));
            return $response;
        }
    ]
];

