<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class OptionalAuth
{
    /**
     * Handle an incoming request.
     * If token is present, validate it. If not, allow request without authentication.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        
        if ($token) {
            $accessToken = PersonalAccessToken::findToken($token);
            
            if ($accessToken) {
                $user = $accessToken->tokenable;
                
                if ($user) {
                    auth('api')->setUser($user);
                    // Also set the current access token for the request
                    $request->setUserResolver(fn() => $user);
                } else {
                    return response()->json([
                        'error' => 'Unauthenticated',
                        'message' => 'Invalid token or user not found.'
                    ], 401);
                }
            } else {
                return response()->json([
                    'error' => 'Unauthenticated',
                    'message' => 'Invalid or expired token.'
                ], 401);
            }
        }
        
        return $next($request);
    }
}

