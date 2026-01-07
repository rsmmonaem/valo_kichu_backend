<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommerceController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API v1 - Customer Routes
|--------------------------------------------------------------------------
| These routes require customer authentication (auth:api)
*/

Route::middleware('auth:api')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/user', [AuthController::class, 'userInfo']);
    Route::match(['put', 'post'], '/auth/user', [AuthController::class, 'updateProfile'])
        ->middleware('parse.multipart.put');
    Route::put('/auth/update-fcm-token', [AuthController::class, 'updateFcmToken']);
    Route::put('/auth/update-fcm-token/', [AuthController::class, 'updateFcmToken']); // Support trailing slash
    Route::delete('/auth/delete-account/{id}', [AuthController::class, 'deleteAccount']);


    // Shop routes
    Route::match(['put', 'post'], '/auth/update-shop-info', [AuthController::class, 'updateShopInfo'])
        ->middleware('parse.multipart.put');
    
    // Address routes
    Route::get('/auth/addresses', [AuthController::class, 'getAddresses']);
    Route::get('/auth/addresses/{id}', [AuthController::class, 'getAddress']);
    Route::post('/auth/addresses', [AuthController::class, 'createAddress']);
    Route::put('/auth/addresses/{id}', [AuthController::class, 'updateAddress']);
    Route::patch('/auth/addresses/{id}', [AuthController::class, 'updateAddress']);
    Route::delete('/auth/addresses/{id}', [AuthController::class, 'deleteAddress']);
    
    // Commerce protected routes
    Route::get('/commerce/favourites', [CommerceController::class, 'favouriteProducts']);
    Route::post('/commerce/favourites/add/{item_id}', [CommerceController::class, 'addFavourite']);
    Route::delete('/commerce/favourites/remove/{item_id}', [CommerceController::class, 'removeFavourite']);
    Route::get('/commerce/notifications', [CommerceController::class, 'notifications']);
    
    // Order routes
    Route::post('/order/cart', [OrderController::class, 'addToCart']);
    Route::put('/order/cart/{cart_id}', [OrderController::class, 'updateCart']);
    Route::get('/order/cart', [OrderController::class, 'cartList']);
    Route::delete('/order/cart/{item_id}', [OrderController::class, 'removeFromCart']);
    Route::post('/order/checkout', [OrderController::class, 'checkout']);
    Route::delete('/order/cancel/{order_id}', [OrderController::class, 'cancelOrder']);
    Route::get('/order/info/{order_id}', [OrderController::class, 'orderInfoDetail']);
    Route::get('/order/info', [OrderController::class, 'ordersByStatus']);
    Route::post('/order/apply-coupon', [OrderController::class, 'applyCoupon']);
    Route::post('/order/reviews/post', [OrderController::class, 'postReview']);
    
    // Payment routes
    Route::post('/payment/init', [PaymentController::class, 'initPayment']);
    Route::post('/payment/complete', [PaymentController::class, 'completePayment']);
});

