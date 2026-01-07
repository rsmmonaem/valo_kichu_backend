<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

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
        $token = $request->header('Authorization');
        
        if ($token) {
            $token = str_replace('Bearer ', '', $token);
            $token = trim($token);
            
            if (!empty($token)) {
                try {
                    JWTAuth::setToken($token);
                    $user = JWTAuth::authenticate();
                    
                    if ($user) {
                        auth('api')->setUser($user);
                    } else {
                        return response()->json([
                            'error' => 'Unauthenticated',
                            'message' => 'Invalid token or user not found.'
                        ], 401);
                    }
                } catch (JWTException $e) {
                    return response()->json([
                        'error' => 'Unauthenticated',
                        'message' => 'Invalid or expired token.'
                    ], 401);
                } catch (\Throwable $e) {
                    return response()->json([
                        'error' => 'Unauthenticated',
                        'message' => 'Token validation failed.'
                    ], 401);
                }
            }
        }
        
        return $next($request);
    }
}

