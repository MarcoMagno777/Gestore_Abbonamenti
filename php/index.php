<?php
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/controllers/AlunniController.php';

$app = AppFactory::create();

$app->get('/account/{idA}/subscriptions', "AccountController:index");
$app->get('/account/{id}', "AccountController:account");
$app->get('/account/{idA}/subscriptions/{idS}', "AccountController:detailsSubscription");
$app->post('/account/{idA}/subscriptions', "AccountController:addSubscription");
$app->put('/account/{idA}/subscriptions/{idS}', "AccountController:updateSubscription");
$app->delete('/account/{idA}/subscriptions/{idS}', "AccountController:deleteSubscription");
$app->post('/login', "AccountController:login");
$app->get('/admin/accounts', "AdminController:index");
$app->delete('/admin/remove/{idA}', "AdminController:deleteAccount");
$app->delete('/admin/remove', "AdminController:deleteAccounts");
$app->put('/admin/update/{idA}', "AdminController:updatePassword");


$app->run();
