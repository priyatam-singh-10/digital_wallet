<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;

class JwtAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized or token invalid'
            ], 401);
        }

        return $next($request);
    }
}
