<?php

namespace App\Http\Middleware;

use App\Models\AccessToken;
use App\Models\User;
use Carbon\Carbon;
use Closure;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (!$request->header('Authorization')) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $check = $this->isValidToken($request->header('Authorization'));

        if (!$check || $check->expires_at < Carbon::now()) {
            return response()->json(['message' => 'Unauthorized Token Expired'], 401);
        }

        $user = User::where('user_id', $check->user_id)->first();

        $request->merge([
            'user_id'   => $user->user_id,
            'name'      => $user->name,
            'email'     => $user->email,
            'profile'   => $user->profile,
        ]);

        return $next($request);
    }

    private function isValidToken($token)
    {
        $decript = base64_decode($token);
        $result = explode('|', $decript);

        $check = AccessToken::where('access_id', $result[1])->first();

        return $check;
    }
}
