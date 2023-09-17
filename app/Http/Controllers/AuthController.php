<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\AccessToken;
use App\Models\Client;
use App\Models\RefreshToken;
use Illuminate\Support\Facades\Auth;
use App\Models\Token;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Crypt;
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

    public function register(Request $request)
    {
        $this->validate($request, [
            'email'             => 'required|email|unique:users,email',
            'password'          => 'required|min:8',
            'current_password'  => 'required|same:password', // Menambahkan aturan same
        ]);


        $name     = 'ESI Games';
        $email  = $request->input('email');
        $password = Hash::make($request->input('password'));

        $id = Str::uuid();

        User::create([
            'user_id'   =>  $id,
            'name'      =>  $name,
            'email'     =>  $email,
            'password'  =>  $password,
            'is_active' =>  0
        ]);

        $code = Str::random(32);

        Token::create([
            'user_id'   => $id,
            'token'     => $code
        ]);

        // send email confirmation
        // $user = [
        //     'email' => $email,
        //     'token' => $code,
        // ];

        // $dataSend = Crypt::encrypt($user);

        // Mail::to($user['email'])->queue(new Verification($dataSend));

        return Response('Success register user', 200);
    }

    public function login(Request $request)
    {
        $this->validate($request, [
            'email'             => 'required|email',
            'password'          => 'required|min:8',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $user = User::where('email', $email)->first();
        if (!$user) {
            return response()->json(['message' => 'Login failed'], 401);
        }

        $isValidPassword = Hash::check($password, $user->password);
        if (!$isValidPassword) {
            return response()->json(['message' => 'Login failed'], 401);
        }

        $clientSecret = Client::where('id', $request->app_id)->first();

        $generateToken = bin2hex(random_bytes(32));

        $created = Carbon::now();
        $expired = $created->addDays(30);

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
            'expires_at' => $expired,
            'revoked'    => 0,
        ]);
        $accessToken = base64_encode($user->user_id . '|' . $generateToken);
        $refreshToken = base64_encode($refresh_id . '|' . $generateToken);

        $result = [
            'name'          => $user->name,
            'email'         => $user->email,
            'token'         => $accessToken,
            'refresh_token' => $refreshToken,
            'expired_at'    => $expired
        ];

        return response()->json($result);
    }
}
