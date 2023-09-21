<?php

namespace App\Http\Controllers;

use App\Helpers\AllFunction;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccessToken;
use App\Models\Client;
use App\Models\RefreshToken;
use App\Models\Token;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function login(Request $request)
    {

        $check = null;

        $this->validate($request, [
            'email'             => 'required|email',
            'token'             => 'required|min:8',
        ]);

        $email = $request->input('email');


        $user = User::where('email', $email)->first();


        if (!$user) {
            $token = strtoupper(Str::random(8));

            $last = User::latest()->first();

            $id = AllFunction::generateId($last);

            User::create([
                'user_id'   => $id,
                'name'      => 'ESI GAMES',
                'email'     => $request->email,
                'token'     => $token,
                'is_active' => 1
            ]);

            $user = User::where('email', $email)->first();
        } else {
            // check token
            if ($request->token !== $user->token) {
                return AllFunction::response(401, 'Failed', 'Login Failed, Token not valid');
            }
        }

        // check token

        $clientSecret = Client::where('id', $request->app_id)->first();

        $generateToken = bin2hex(random_bytes(32));

        $created = Carbon::now();
        $expired = $created->addDays(7);
        $refreshExpired = $created->addDays(14);


        AccessToken::create([
            'access_id' => $generateToken,
            'user_id'   => $user->user_id,
            'client_id' => $clientSecret->id,
            'name'      => $clientSecret->name,
            'revoke'    => 0,
            'expires_at' => $expired,
            'created_at' => $created,
        ]);

        $refresh_id = str::uuid();
        RefreshToken::create([
            'id'        => $refresh_id,
            'access_id' => $generateToken,
            'expires_at' => $refreshExpired,
            'revoked'    => 0,
        ]);

        $accessToken = AllFunction::generateAccessToken($request->app_id, $user->user_id, $generateToken, $expired);

        $refreshToken = AllFunction::generateRefreshToken($request->app_id, $user->user_id, $generateToken, $refreshExpired);

        $result = [
            'token'         => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at'    => $expired->timestamp
        ];

        return AllFunction::response(200, 'OK', 'Login Success', $result);
    }
}
