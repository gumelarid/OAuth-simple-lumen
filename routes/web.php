<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;

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
$router->post('/client', 'Auth\ClientController@store');

// create user
$router->post('/auth/send-token', 'Auth\AuthController@sendToken');
$router->post('/auth/login', 'Auth\AuthController@login');

$router->post('/auth/sosmed', 'Auth\AuthController@checkId');

$router->post('/user/binding', ['middleware' => 'checkToken', 'uses' => 'Auth\UserController@binding']);

$router->get('/user', ['middleware' => 'checkToken', function (Request $request) {
    return AllFunction::response(200, 'OK', 'get user success', $request->all());
}]);




// ============================================================== payment =======================================================

$router->post('/api/v1/transaction/notify', 'Topup\TransactionController@notify');
$router->get('/api/v1/transaction/notify', 'Topup\TransactionController@notify');


$router->get('/gethistory', ['middleware' => 'checkToken', 'uses' => 'Topup\HistoryController@index']);
$router->group(['prefix' => 'api/v1', 'middleware' => 'api-key'], function () use ($router) {

    // game list
    $router->get('/gamelist', 'Topup\GameController@index');
    $router->get('/gamedetail', 'Topup\GameController@gameDetail');


    // get Country
    $router->get('/country', 'Topup\CountryController@getCountry');

    // get Price Point
    $router->get('/pricepoint', 'Topup\PaymentController@priceList');


    // payment
    $router->get('/payment', 'Topup\PaymentController@index');
    $router->get('/allpayment', 'Topup\PaymentController@getAllPayment');


    // transaction
    $router->post('/transaction', 'Topup\TransactionController@transaction');
    $router->get('/confirmation', 'Topup\TransactionController@confirmation');

    $router->post('/save-reference', 'Topup\TransactionController@saveReverence');

    // check expired
    $router->get('/expired', 'Topup\TransactionController@expired');


    //send otp
    $router->post('/send-otp', 'Auth\AuthController@sendOtp');
    $router->post('/check-otp', 'Auth\AuthController@checkOtp');
});
