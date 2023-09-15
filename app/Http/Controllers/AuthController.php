<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Token;
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
    }
}
