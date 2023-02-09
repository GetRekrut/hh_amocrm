<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/hh/test', 'AmocrmController@test');

//hh
$router->get('/hh/auth', 'Controller@authHeadHunter');
$router->get('/hh/refresh_token', 'Controller@refreshToken');
$router->get('/hh/get_token', 'Controller@getToken');

$router->post('/hh/set_webhook', 'Controller@setWebHook');
$router->get('/hh/check_webhook', 'Controller@checkWebHook');
$router->delete('/hh/delete_webhook', 'Controller@deleteWebHook');

$router->get('/hh/check_negotiations', 'ResponseController@checkNegotiations');
$router->post('/hh/get_hook', 'ResponseController@getHook');

