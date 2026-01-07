<?php

use Illuminate\Support\Facades\Route;

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
