<?php

namespace App\Http\Controllers\Auth;

use DateTime;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Token;
use App\Models\Client;
use App\Models\User_token;
use App\Models\AccessToken;
use Illuminate\Support\Str;
use App\Helpers\AllFunction;
use App\Models\RefreshToken;
use Illuminate\Http\Request;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

    public function sendToken(Request $request)
    {
        dd($request);
    }

    public function login(Request $request)
    {

        try {
            DB::beginTransaction();
            $check = null;

            $this->validate($request, [
                'email'             => 'required|email',
            ]);

            $email = $request->input('email');


            $user = User::where('email', $email)->first();


            if (!$user) {
                $token = \strtoupper(Str::random(7));

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

                return AllFunction::response(200, 'OK', 'Check Your Email and input your Token', $email);
            } else {
                // check token
                if ($request->token !== $user->token) {
                    return AllFunction::response(401, 'Failed', 'Login Failed, Token not valid');
                }

                User::where('email', $email)->update([
                    'token'     => null,
                ]);
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

            DB::commit();
            return AllFunction::response(200, 'OK', 'Login Success', $result);
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }

    // auth google and facebook
    public function checkId(Request $request)
    {

        try {
            DB::beginTransaction();
            $user = SocialAccount::where('provider_user_id', $request->id)->first();

            if (!$user) {
                $last = User::latest()->first();
                $id = AllFunction::generateId($last);

                $d = User::create([
                    'user_id'   => $id,
                    'email' => $request->email,
                    'profile' => $request->profile,
                    'token' => null,
                    'name' => $request->name,
                    'is_active' => 1,
                ]);

                SocialAccount::create([
                    'account_Id'    => Str::uuid(),
                    'user_id' => $id,
                    'provider' => $request->provider,
                    'provider_user_id' => $request->id,
                    'access_token' => $request->access_token
                ]);

                $dt = User::where('user_id', $id)->first();
            } else {
                SocialAccount::where('provider_user_id', $request->id)->update([
                    'access_token' => $request->access_token
                ]);


                $dt = User::where('user_id', $user->user_Id)->first();
            }



            // check token

            $clientSecret = Client::where('id', $request->app_id)->first();

            $generateToken = bin2hex(random_bytes(32));

            $created = Carbon::now();
            $expired = $created->addDays(7);
            $refreshExpired = $created->addDays(14);


            AccessToken::create([
                'access_id' => $generateToken,
                'user_id'   => $dt->user_id,
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



            $accessToken = AllFunction::generateAccessToken($request->app_id, $dt->user_Id, $generateToken, $expired);

            $refreshToken = AllFunction::generateRefreshToken($request->app_id, $dt->user_Id, $generateToken, $refreshExpired);

            $result = [
                'token'         => $accessToken,
                'refresh_token' => $refreshToken,
                'expired_at'    => $expired->timestamp
            ];

            DB::commit();
            return AllFunction::response(200, 'OK', 'Login Success', $result);
        } catch (\Throwable $th) {
            DB::rollback();
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }


    public function sendOtp(Request $request)
    {
        // try {
        $no = $request->phone;
        $provider = $request->provider;
        $otp = rand(100000, 999999);
        $dt = null;

        if (!$no) {
            return AllFunction::response(400, 'Validation failed', 'no Validation failed');
        }

        if (!$provider) {
            return AllFunction::response(400, 'Validation failed', 'provider Validation failed');
        }

        if ($provider == '2') { // sms
            $provider = '2';
            $message = 'Kode OTP kamu adalah : ' . $otp . ', berlaku selamat 5 menit';
            $message = urlencode($message);
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://dnymobile.com/api/v3/sms/send?recipient=" . "+62" . $no . "&sender_id=DNY&message=" . $message,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Authorization: Bearer 686|5MXOJBpTmsnxosA4MbSXUV1kxWkRZ3I5cw8z5RWJ' //masukkan token anda
                ),
            ));
            $response = curl_exec($curl);
            curl_close($curl);
        } else {
            // whatsapp
            $provider = '1';
            $curl = curl_init();
            $data = [
                'target' => "+62" . $no,
                'message' => "Your OTP : " . $otp
            ];

            curl_setopt(
                $curl,
                CURLOPT_HTTPHEADER,
                array(
                    "Authorization: fs9Yyu6!7YChwRqGQ8FZ",
                )
            );
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curl, CURLOPT_URL, "https://api.fonnte.com/send");
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($curl);
            curl_close($curl);
        }

        $user = User::where('phone', $no)->first();

        if (!$user) {
            $dt = User::create([
                'phone' => $no,
                'name' => 'ESI GAMER',
                'is_active' => 1,
            ]);

            User_token::create([
                'user_id' => $dt->id,
                'otp' => $otp,
                'provider' => $provider,
            ]);
        }

        $userData = User::where('phone', $no)->first();

        User_token::where('user_id', $userData->id)->update([
            'otp' => $otp,
            'provider' => $provider
        ]);

        $res = [
            'phone' => $no,
            'provider' => $provider,
        ];

        return AllFunction::response(201, 'OK', "OTP Send to " . '+62' . $no, $res);
        // } catch (\Throwable $th) {
        //     return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        // }
    }

    public function checkOtp(Request $request)
    {
        try {
            $no = $request->phone;
            $otp = $request->otp;

            if (!$no) {
                return AllFunction::response(400, 'Validation failed', 'no Validation failed');
            }

            if (!$provider) {
                return AllFunction::response(400, 'Validation failed', 'provider Validation failed');
            }

            $user = User::where('phone', $no)->first();

            if (!$user) {
                return AllFunction::response(400, 'Validation failed', 'User not valid');
            }

            $check = User_token::where('user_id', $user->id)->first();
            if ($check->otp !== $otp) {
                return AllFunction::response(400, 'Validation failed', 'OTP not valid');
            }

            User_token::where('user_id', $user->id)->update([
                'otp' => null,
            ]);

            if ($token = Auth::guard('api')->login($user)) {
                return $this->respondWithToken($token);
            }
        } catch (\Throwable $th) {
            return AllFunction::response(300, 'BAD REQUEST', 'internal server error');
        }
    }
}
