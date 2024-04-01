<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class APITokenAuthenticationMiddleware
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
        $user = User::where('api_token', $request->header('Authorization'))->first();
        if ($user) {
            return $next($request);
        } else {
            return response(['error_message' => 'Unauthorized Access!'], 401, ['Content-Type' => 'application/json']);
        }
    }
}
