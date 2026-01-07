<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommerceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\ConfigController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API v1 - Public Routes
|--------------------------------------------------------------------------
| These routes are accessible without authentication
*/

// Authentication - Public
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/send-verification', [AuthController::class, 'sendVerification'])->middleware('optional.auth');
Route::post('/auth/verify-otp', [AuthController::class, 'verification'])->middleware('optional.auth');
Route::post('/auth/social-login', [AuthController::class, 'socialLogin']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/auth/verify-reset-token', [AuthController::class, 'verifyResetToken']);
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/auth/reset-password/', [AuthController::class, 'resetPassword']); // With trailing slash
Route::post('/auth/re-set-password', [AuthController::class, 'resetPassword']); // Alternative route format
Route::post('/auth/re-set-password/', [AuthController::class, 'resetPassword']); // Alternative route format with trailing slash

// Commerce - Public
Route::get('/commerce/products', [CommerceController::class, 'index']);
Route::get('/commerce/product-list', [CommerceController::class, 'productList']);
Route::get('/commerce/product-detail/{id}', [CommerceController::class, 'productDetail']);
Route::get('/commerce/brand-list', [CommerceController::class, 'brandList']);
Route::get('/commerce/category-list', [CommerceController::class, 'categoryList']);
Route::get('/commerce/category-list/{id}', [CommerceController::class, 'subcategoryList']);
Route::get('/commerce/brand-with-products', [CommerceController::class, 'brandWithProducts']);
Route::get('/commerce/brand-wise-products/{brand_id}', [CommerceController::class, 'brandWiseProducts']);
Route::get('/commerce/categories-with-products', [CommerceController::class, 'categoriesWithProducts']);
Route::get('/commerce/category-wise-products/{category_id}', [CommerceController::class, 'categoryWiseProducts']);
Route::get('/commerce/items-sections', [CommerceController::class, 'productSections']);
Route::get('/commerce/recommended-products', [CommerceController::class, 'recommendedProducts']);
Route::get('/commerce/vendors', [CommerceController::class, 'vendorList']);
Route::get('/commerce/vendors/{id}', [CommerceController::class, 'vendorDetail']);
Route::get('/commerce/vendors/top', [CommerceController::class, 'topVendors']);
Route::get('/commerce/vendors/{id}/products', [CommerceController::class, 'vendorProducts']);
Route::get('/commerce/vendors/{id}/brands', [CommerceController::class, 'vendorBrands']);
Route::get('/commerce/vendors/{id}/categories', [CommerceController::class, 'vendorCategories']);
Route::get('/commerce/products/{product_id}/reviews', [OrderController::class, 'getReviews']);

// Banner - Public
Route::get('/banner/flash-banners', [BannerController::class, 'flashBanners']);
Route::get('/banner/flash-banners/{id}', [BannerController::class, 'flashBannerDetail']);
Route::get('/banner/banners', [BannerController::class, 'banners']);
Route::get('/banner/banners/{id}', [BannerController::class, 'bannerDetail']);

// Config - Public (optional authentication: validates token if present, allows access if not)
Route::get('/config/app-config', [ConfigController::class, 'appConfig'])->middleware('optional.auth');

// Division, District, City - Public
Route::get('/division/list', [ConfigController::class, 'divisionList'])->middleware('optional.auth');
Route::get('/district/list', [ConfigController::class, 'districtList'])->middleware('optional.auth');
Route::get('/city/list', [ConfigController::class, 'cityList'])->middleware('optional.auth');

// Payment Callbacks - Public
Route::get('/payment/success', [PaymentController::class, 'paymentSuccess']);
Route::get('/payment/failed', [PaymentController::class, 'paymentFailed']);
Route::get('/payment/cancel', [PaymentController::class, 'paymentCancel']);

