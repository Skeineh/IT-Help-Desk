<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        $user = JWTAuth::parseToken()->authenticate();

        if ($user && $user->MustChangePassword) {
            return response()->json([
                'message' => 'Password change required before continuing.',
                'must_change_password' => true,
            ], 409);
        }

        return $next($request);
    }
}
