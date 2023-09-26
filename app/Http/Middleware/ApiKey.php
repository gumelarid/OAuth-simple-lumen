<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $apiKey = env('API_KEY');

        $clientApiKey = $request->header('X-Api-Key');

        if ($clientApiKey !== $apiKey) {
            return response()->json([
                'code' => 401,
                'status' => 'Unauthorized',
                'data' => 'Unauthorized',
            ], 401);
        }

        return $next($request);
    }
}
