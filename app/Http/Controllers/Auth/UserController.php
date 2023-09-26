<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\AllFunction;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use App\Models\SocialAccount;

class UserController extends Controller
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


    public function binding(Request $request)
    {
        try {
            DB::beginTransaction();

            $user_id        = $request->user_id;
            $name           = $request->name;
            $email          = $request->email;
            $profile        = $request->profile;
            $provider       = $request->provider;
            $provider_id    = $request->id;
            $accessToken    = $request->access_token;

            $binding = SocialAccount::where('provider_user_id', $provider_id)->first();

            if ($binding) {
                return AllFunction::response(400, 'FAILED', 'Binding failed, account find and binding by ' . $user_id);
            }

            $d = User::create([
                'user_id'   => $user_id,
                'email' => $email,
                'profile' => $profile,
                'token' => null,
                'name' => $name,
                'is_active' => 1,
            ]);

            SocialAccount::create([
                'account_Id'    => Str::uuid(),
                'user_id' => $user_id,
                'provider' => $provider,
                'provider_user_id' => $provider_id,
                'access_token' => $accessToken
            ]);

            $dt = User::where('user_id', $user_id)->first();

            DB::commit();
            return AllFunction::response(201, 'OK', 'Binding Success by user id ' . $user_id, $dt);
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
