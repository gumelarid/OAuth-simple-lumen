<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Models\SocialAccount;
use App\Models\User;

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
    $user_id = $request->user_id;
    $user = User::with('socialAccount')->where('user_id', $user_id)->first();


    if ($user) {

        if ($user->linkedId !== null) {
            $checkLink = User::with('socialAccount')->where('user_id', $user->linkedId)->first();



            $data = [
                'user_id' => $user->linkedId,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->profile,
                'phone' => $user->phone,
                'provider' => $user->socialAccount->provider,
                'bind' => []
            ];

            $socialProvider = User::with('socialAccount')->where('linkedId', $checkLink->user_id)->get();

            if ($socialProvider) {
                foreach ($socialProvider as $linked) {
                    if ($linked->socialAccount->provider == 'google') {
                        $binding = [
                            'google' => [
                                'provider' => $linked->socialAccount->provider,
                                'user_id' => $linked->user_id,
                                'provider_id' => $linked->socialAccount->provider_id,
                                'email' => $linked->email,
                            ]
                        ];

                        $data['bind'][] = $binding;
                    }

                    if ($linked->socialAccount->provider == 'facebook') {
                        $binding = [
                            'facebook' => [
                                'provider' => $linked->socialAccount->provider,
                                'user_id' => $linked->user_id,
                                'provider_id' => $linked->socialAccount->provider_id,
                                'email' => $linked->email,
                            ]
                        ];

                        $data['bind'][] = $binding;
                    }
                }
            }
        } else {
            $data = [
                'user_id' => $user->user_id,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->profile,
                'phone' => $user->phone,
                'provider' => $user->socialAccount->provider,
                'bind' => []
            ];


            $checkLink = User::with('socialAccount')->where('linkedId', $user_id)->get();

            if ($checkLink) {
                foreach ($checkLink as $linked) {
                    if ($linked->socialAccount->provider == 'google') {
                        $binding = [
                            'google' => [
                                'provider' => $linked->socialAccount->provider,
                                'user_id' => $linked->user_id,
                                'provider_id' => $linked->socialAccount->provider_id,
                                'email' => $linked->email,
                            ]
                        ];

                        $data['bind'][] = $binding;
                    }

                    if ($linked->socialAccount->provider == 'facebook') {
                        $binding = [
                            'facebook' => [
                                'provider' => $linked->socialAccount->provider,
                                'user_id' => $linked->user_id,
                                'provider_id' => $linked->socialAccount->provider_id,
                                'email' => $linked->email,
                            ]
                        ];

                        $data['bind'][] = $binding;
                    }
                }
            }
        }
        return AllFunction::response(200, 'OK', 'get user success',  $data);
    } else {
        return AllFunction::response(404, 'Not Found', 'User not found', []);
    }
}]);


// $router->get('/user', ['middleware' => 'checkToken', function (Request $request) {

//     $user_id = $request->user_id;
//     $name = $request->name;
//     $email = $request->email;
//     $profile = $request->profile;
//     $phone = $request->phone;

//     $bind = Bind::where('user_id', $user_id)->get();

//     $data = [
//         'user_id'   => $user_id,
//         'name' => $name,
//         'email' => $email,
//         'profile'   => $profile,
//         'phone' => $phone,
//     ];

//     $dt = [];


//     foreach ($bind as $b) {
//         $prov = SocialAccount::where('account_id', $b->account_id)->first();
//         $userBind = User::where('user_id', $prov->user_Id)->first();


//         if ($prov->provider == 'google') {
//             $binding['google'] = [
//                 'provider' => $prov->provider,
//                 'user_id'   => $userBind->user_id,
//                 'provider_id'   => $prov->provider_user_id,
//                 'email'     => $userBind->email,
//             ];
//         }

//         if ($prov->provider == 'facebook') {
//             $binding['facebook'] = [
//                 'provider' => $prov->provider,
//                 'user_id'   => $userBind->user_id,
//                 'provider_id'   => $prov->provider_user_id,
//                 'email'     => $userBind->email,
//             ];
//         }

//         array_push($dt, $binding);
//     }

//     $data['bind'] = $dt;

//     return AllFunction::response(200, 'OK', 'get user success', $data);
// }]);




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
