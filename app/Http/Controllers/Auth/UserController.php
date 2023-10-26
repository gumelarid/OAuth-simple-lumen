<?php

namespace App\Http\Controllers\Auth;

use App\Models\Bind;
use App\Models\User;
use Illuminate\Support\Str;
use App\Helpers\AllFunction;
use Illuminate\Http\Request;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
            $name           = $request->nameBind;
            $email          = $request->emailBind;
            $provider       = $request->providerBind;
            $provider_id    = $request->idBind;
            $accessToken    = $request->accessTokenBind;


            // check apakah user ada
            $user = User::where('user_id', $user_id)->first();

            if (!$user) {
                return AllFunction::response(400, 'FAILED', 'Binding failed, account not find');
            }

            // check apakah email tersebut sudah terkoneksi
            $userLink = User::where('provider_id', $provider_id)->first();

            if ($userLink) {
                if ($userLink->linkedId !== null) {
                    return AllFunction::response(400, 'FAILED', 'Binding failed, account binding');
                }
            }



            // jika belum ada
            $last = User::latest()->first();
            $id = AllFunction::generateId($last);

            if ($provider == 'facebook') {
                $img = 'https://graph.facebook.com/v3.3/' . $provider_id . '/picture?type=normal' . '&access_token=' . $accessToken;
            } else {
                $img = $request->profile;
            }

            // buat user baru dengan linkedId ke user_id
            $d = User::create([
                'user_id'   => $id,
                'email' => $email,
                'profile' => $img,
                'name' => $name,
                'provider_id' => $provider_id,
                'linkedId'  => $user_id,
                'is_active' => 1,
            ]);


            SocialAccount::create([
                'provider' => $provider,
                'provider_id' => $provider_id,
                'access_token' => $accessToken
            ]);

            DB::commit();
            return AllFunction::response(201, 'OK', 'Binding Success by user id ' . $user_id . '');
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    public function unlink(Request $request)
    {
        try {
            $user_id       = $request->user_id;

            // check apakah user ada
            $user = User::where('user_id', $userId)->first();

            if (!$user) {
                return AllFunction::response(400, 'FAILED', 'Binding failed, account not find');
            }

            $link = User::where('user_id', $user_id)->delete();

            return AllFunction::response(200, 'OK', 'Binding failed, account not find');
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
