<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Helpers\AllFunction;
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

$router->post('/auth/sosmed', 'AuthController@checkId');


$router->get('/user', ['middleware' => 'checkToken', function (Request $request) {
    return AllFunction::response(200, 'OK', 'get user success', $request->all());
}]);
