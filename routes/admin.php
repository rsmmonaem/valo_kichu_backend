<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\UploadController;
use App\Http\Controllers\Admin\SettingController;

// Admin Routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::apiResource('banners', BannerController::class);
    Route::apiResource('shipping-methods', \App\Http\Controllers\Admin\ShippingMethodController::class);
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
});