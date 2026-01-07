<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Store Routes
Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index']);
Route::get('/products/{id}', [\App\Http\Controllers\ProductController::class, 'show']);
Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index']);
Route::get('/banners', [\App\Http\Controllers\BannerController::class, 'index']);
Route::get('/settings', [\App\Http\Controllers\SettingController::class, 'index']); // Public Settings

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // Customer Routes
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index']);
    Route::post('/orders', [\App\Http\Controllers\OrderController::class, 'store']);
    Route::get('/orders/{id}', [\App\Http\Controllers\OrderController::class, 'show']);

    // Admin Routes
    Route::group(['prefix' => 'admin', 'middleware' => ['auth:sanctum']], function () {
        Route::get('/dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'stats']);
        Route::apiResource('categories', \App\Http\Controllers\Admin\CategoryController::class);
        Route::apiResource('products', \App\Http\Controllers\Admin\ProductController::class);
        Route::apiResource('orders', \App\Http\Controllers\Admin\OrderController::class)->only(['index', 'show', 'update']);
        Route::apiResource('banners', \App\Http\Controllers\Admin\BannerController::class);
        Route::get('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'index']);
        Route::post('/settings', [\App\Http\Controllers\Admin\SettingController::class, 'update']);
    });
});

Route::prefix('v1')->group(function () {
    require __DIR__ . '/Api/v1/public.php';
    require __DIR__ . '/Api/v1/customer.php';
});
