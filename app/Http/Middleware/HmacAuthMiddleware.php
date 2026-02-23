<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Log;

class HmacAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKeyUUID = $request->header('X-API-Key');
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (!$apiKeyUUID || !$timestamp || !$signature) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing security headers.'
            ], 401);
        }

        // Validate timestamp (5 minute window)
        if (abs(time() - $timestamp) > 300) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request expired.'
            ], 401);
        }

        $apiKey = ApiKey::where('key', $apiKeyUUID)->where('is_active', true)->first();

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive API key.'
            ], 401);
        }

        // Verify Signature
        $expectedSignature = hash_hmac('sha256', $timestamp . $apiKeyUUID, $apiKey->secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature.'
            ], 401);
        }

        // Authenticate the user for this request
        auth()->login($apiKey->user);

        return $next($request);
    }
}
