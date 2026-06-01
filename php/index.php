<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/BaseController.php';
require __DIR__ . '/controllers/AuthController.php';
require __DIR__ . '/controllers/AbbonamentiController.php';
require __DIR__ . '/controllers/AccountController.php';
require __DIR__ . '/controllers/DashboardController.php';
require __DIR__ . '/controllers/AdminController.php';

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$app->add(function (Request $request, $handler) {
    $origin = $request->getHeaderLine('Origin') ?: 'http://localhost:4200';

    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
    } else {
        $response = $handler->handle($request);
    }

    return $response
        ->withHeader('Access-Control-Allow-Origin', $origin)
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails,
    bool $logErrors,
    bool $logErrorDetails
) use ($app) {
    $payload = [
        'error' => 'Errore backend',
        'message' => $exception->getMessage(),
    ];

    $response = $app->getResponseFactory()->createResponse(500);
    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withHeader('Access-Control-Allow-Origin', $request->getHeaderLine('Origin') ?: 'http://localhost:4200')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withStatus(500);
});

$app->get('/test', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("Test page");
    return $response;
});

$app->get('/up', function (Request $request, Response $response, array $args) {
    $response->getBody()->write("OK");
    return $response;
});

$app->get('/', function (Request $request, Response $response, array $args) {
    $payload = json_encode(['status' => 'ok']);
    $response->getBody()->write($payload);
    return $response->withHeader("Content-type", "application/json");
});

$app->get('/hello/{name}', function (Request $request, Response $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");
    return $response;
});

$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

$app->post('/api/auth/login', 'AuthController:login');
$app->post('/api/auth/register', 'AuthController:register');
$app->post('/api/auth/logout', 'AuthController:logout');
$app->get('/api/auth/me', 'AuthController:me');

$app->get('/api/dashboard', 'DashboardController:index');

$app->get('/api/account/{id}', 'AccountController:show');

$app->get('/api/abbonamenti', 'AbbonamentiController:index');
$app->post('/api/abbonamenti', 'AbbonamentiController:create');
$app->put('/api/abbonamenti/{id}', 'AbbonamentiController:update');
$app->delete('/api/abbonamenti/{id}', 'AbbonamentiController:delete');

// Alias singolare, utile se il frontend o i test usano il nome della tabella.
$app->get('/api/abbonamento', 'AbbonamentiController:index');
$app->post('/api/abbonamento', 'AbbonamentiController:create');

$app->get('/api/admin/accounts', 'AdminController:accounts');
$app->get('/api/admin/users/count', 'AdminController:countUsers');
$app->delete('/api/admin/users', 'AdminController:deleteAllUsers');
$app->put('/api/admin/accounts/{id}/reset-password', 'AdminController:resetPassword');
$app->delete('/api/admin/accounts/{id}', 'AdminController:deleteAccount');
$app->get('/api/admin/abbonamenti', 'AdminController:abbonamenti');
$app->delete('/api/admin/abbonamenti/{id}', 'AdminController:deleteAbbonamento');

$app->run();
