<?php

namespace App\Http\Controllers;

use App\Services\MohasagorImportService;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class ProductImportController extends Controller
{
    protected $importService;

    public function __construct(MohasagorImportService $importService)
    {
        $this->importService = $importService;
    }

    public function importProducts(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : 'unknown';
        Log::info('Product import initiated by user: ' . $userId);

        return response()->stream(function () {
            // Disable output buffering
            if (ob_get_level()) ob_end_clean();
            
            foreach ($this->importService->importStream() as $message) {
                echo "data: " . json_encode($message) . "\n\n";
                flush();
            }
            
            echo "data: " . json_encode(['type' => 'done']) . "\n\n";
            flush();
            
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no' // Prevent Nginx buffering
        ]);
    }

    public function debug(Request $request)
    {
        try {
            // Check connectivity to Mohasagor API
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'api-key' => env('MOHASAGOR_API_KEY'),
                'secret-key' => env('MOHASAGOR_API_SECRET'),
                'Accept' => 'application/json',
            ])->get('https://mohasagor.com.bd/api/reseller/product');

            $status = $response->status();
            $success = $response->successful();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Debug check completed',
                'api_connection' => [
                    'url' => 'https://mohasagor.com.bd/api/reseller/product',
                    'status' => $status,
                    'success' => $success,
                ],
                'env_check' => [
                    'has_api_key' => !empty(env('MOHASAGOR_API_KEY')),
                    'has_secret_key' => !empty(env('MOHASAGOR_API_SECRET')),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Debug check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
