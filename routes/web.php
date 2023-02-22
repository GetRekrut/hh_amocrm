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

$router->post('/hh/test', 'TestController@getHook');

//hh
$router->get('/hh/auth', 'AccessController@authHeadHunter');
$router->get('/hh/refresh_token', 'AccessController@refreshToken');
$router->post('/hh/get_token', 'AccessController@getToken');

$router->post('/hh/set_webhook', 'HookController@setWebHook');
$router->get('/hh/check_webhook', 'HookController@checkWebHook');
$router->delete('/hh/delete_webhook', 'HookController@deleteWebHook');

$router->get('/hh/check_negotiations', 'ResponseController@checkNegotiations');
$router->post('/hh/get_hook', 'ResponseController@getHook');

//amocrm
$router->get('/amocrm/add_applicant', 'AmocrmController@addApplicant');


