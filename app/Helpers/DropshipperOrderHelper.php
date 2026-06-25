<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DropshipperOrderHelper
{
    public static function post(
        string $url,
        array $payload = [],
        array $headers = []
    ) {
        try {

            $response = Http::withHeaders($headers)
                ->acceptJson()
                ->timeout(60)
                ->post($url, $payload);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response' => $response->json()
            ];

        } catch (\Exception $e) {

            Log::error('API Order Error', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'status' => 500,
                'message' => $e->getMessage(),
            ];
        }
    }
}