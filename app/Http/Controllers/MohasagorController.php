<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MohasagorController extends Controller
{
    public function fetchData()
    {
        try {
            $response = Http::withHeaders([
                'api-key'    => env('MOHASAGOR_API_KEY'),
                'secret-key' => env('MOHASAGOR_API_SECRET'),
                'Accept'     => 'application/json',
            ])->get('https://mohasagor.com.bd/api/reseller/product');

            // Check if the API request failed
            if ($response->failed()) {
                return response()->json([
                    'message' => 'Mohasagor API request failed',
                    'error'   => $response->body(),
                ], $response->status());
            }

            // Return the API response data
            return response()->json([
                'message' => 'Data fetched successfully',
                'data'    => $response->json(),
            ]);

        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => 'Server error',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
