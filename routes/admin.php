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
use App\Http\Controllers\Admin\DropshippingAdminController;
use App\Http\Controllers\Admin\ShippingMethodController;
use App\Http\Controllers\Admin\WithdrawalController;

// Admin Routes
Route::prefix('v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('orders', AdminOrderController::class)->only(['index', 'show', 'update']);
    Route::apiResource('banners', BannerController::class);
    Route::apiResource('shipping-methods', ShippingMethodController::class);
    Route::post('/upload', [UploadController::class, 'upload']);
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);

    // Dropshipping Management
    Route::group(['prefix' => 'dropshipping'], function () {
            // Withdrawal Requests (Admin)
    Route::get('/withdrawals', [WithdrawalController::class, 'index']);
    Route::post('/withdrawals/{id}/approve', [WithdrawalController::class, 'approve']);
    Route::post('/withdrawals/{id}/reject', [WithdrawalController::class, 'reject']);
        Route::get('/settings', [DropshippingAdminController::class, 'getSettings']);
        Route::post('/settings', [DropshippingAdminController::class, 'updateSettings']);
        Route::get('/users', [DropshippingAdminController::class, 'listDropshippers']);
        Route::get('/users/pending', [DropshippingAdminController::class, 'listPendingDropshippers']);
        Route::post('/users/{id}/approve', [DropshippingAdminController::class, 'approveDropshipper']);
        Route::post('/users', [DropshippingAdminController::class, 'storeDropshipper']);
        Route::put('/users/{id}', [DropshippingAdminController::class, 'updateDropshipper']);
        Route::delete('/users/{id}', [DropshippingAdminController::class, 'deleteDropshipper']);
        Route::post('/users/{id}/toggle-status', [DropshippingAdminController::class, 'toggleUserStatus']);
        Route::get('/banned-ips', [DropshippingAdminController::class, 'listBannedIps']);
        Route::post('/banned-ips/{id}/toggle', [DropshippingAdminController::class, 'toggleIpBan']);
    });
});