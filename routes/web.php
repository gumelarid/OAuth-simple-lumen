<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use Illuminate\Http\Request;

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

// create client
$router->post('/client', 'ClientController@store');



// create user
$router->post('/auth/register', 'AuthController@register');
$router->post('/auth/login', 'AuthController@login');


$router->get('/user', ['middleware' => 'checkToken', function (Request $request) {
    return $request->all();
}]);
