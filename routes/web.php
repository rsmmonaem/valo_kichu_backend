<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return view('welcome');
});

Route::fallback(function (\Illuminate\Http\Request $request) {
    if ($request->is('api/*')) {
        return response()->json([
            'status' => 'error',
            'message' => 'Route not found.'
        ], 404);
    }
    return view('welcome');
});

Route::get('/fixall', function () {

    Artisan::call('optimize:clear');
    Artisan::call('queue:restart');

    return response()->json([
        'status' => 'success',
        'optimize_clear' => 'done',
        'queue_restart' => 'done',
        'message' => 'All cleared successfully'
    ]);
});

Route::get('/reset-import', function () {
    $service = new \App\Services\MohasagorImportService();
    $service->resetData();
    
    return response()->json([
        'status' => 'success',
        'message' => 'All products and categories have been deleted. You can now start a fresh import.'
    ]);
});