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
        // 1. Initial Identity Check (Key retrieval)
        $apiKeyUUID = $request->header('X-API-Key') ?? $request->query('key');
        
        if (!$apiKeyUUID) {
            return response()->json([
                'status' => 'error',
                'message' => 'API Key required.'
            ], 401);
        }

        $apiKey = ApiKey::where('key', $apiKeyUUID)->where('is_active', true)->first();

        if (!$apiKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or inactive API key.'
            ], 401);
        }

        // 2. IP Whitelisting
        $allowedIps = $apiKey->settings['allowed_ips'] ?? [];
        if (!empty($allowedIps)) {
            $clientIp = $request->ip();
            if (!in_array($clientIp, $allowedIps)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "IP Address {$clientIp} is not authorized."
                ], 403);
            }
        }

        // 3. Rate Limiting (60 requests per minute per key)
        $cacheKey = "api_rate_limit_{$apiKey->id}";
        $requests = \Cache::get($cacheKey, 0);
        if ($requests >= 60) {
            return response()->json([
                'status' => 'error',
                'message' => 'Rate limit exceeded. Max 60 requests per minute.'
            ], 429);
        }
        \Cache::put($cacheKey, $requests + 1, 60);

        // 4. Authentication Logic
        // Simple Mode (Query Params)
        if ($request->has('key') && $request->has('secret')) {
            if ($apiKey->secret === $request->query('secret')) {
                auth()->login($apiKey->user);
                return $next($request);
            }
        }

        // Secure Mode (HMAC)
        $timestamp = $request->header('X-Timestamp');
        $signature = $request->header('X-Signature');

        if (!$timestamp || !$signature) {
            return response()->json([
                'status' => 'error',
                'message' => 'Missing security headers for secure mode.'
            ], 401);
        }

        // Nonce Expiry (5 minute window as requested)
        if (abs(time() - (int)$timestamp) > 300) {
            return response()->json([
                'status' => 'error',
                'message' => 'Request expired (Nonce timeout).'
            ], 401);
        }

        $expectedSignature = hash_hmac('sha256', $timestamp . $apiKeyUUID, $apiKey->secret);

        if (!hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature.'
            ], 401);
        }

        // Authenticate
        auth()->login($apiKey->user);

        return $next($request);
    }
}
