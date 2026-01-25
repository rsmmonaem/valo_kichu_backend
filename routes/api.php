<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\MohasagorController;
use App\Http\Controllers\MohasagorController1;
use Illuminate\Support\Facades\Artisan;

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

Route::get('/nai/kono/migrations', function(){

    return "Successfully done migraiton";
});

Route::get('/storage-link', function () {
    Artisan::call('storage:link');

    return response()->json([
        'status' => true,
        'message' => 'Successfully done storage link'
    ]);
});

// Mohasagor route
Route::get('/mohasagor/products', [MohasagorController::class, 'fetchData']);
// Route::get('/mohasagor/import', [MohasagorController1::class, 'fetchAndProcessProducts']);
Route::post('/admin/v1/mohasagor/import', [MohasagorController1::class, 'importProducts']);
// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public Store Routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']); // Public Settings

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'API route not found.'
    ], 404);
});

Route::group(['prefix' => 'v1'], function () {
    // Config - Public (optional authentication: validates token if present, allows access if not)
    Route::get('/config/app-config', [ConfigController::class, 'appConfig'])->middleware('optional.auth');


    // Authentication - Public
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
    Route::get('/products', [CommerceController::class, 'index']);
    Route::get('/product-list', [CommerceController::class, 'productList']);
    Route::get('/product-detail/{id}', [CommerceController::class, 'productDetail']);
    Route::get('/brand-list', [CommerceController::class, 'brandList']);
    Route::get('/category-list', [CommerceController::class, 'categoryList']);
    Route::get('/category-list/{id}', [CommerceController::class, 'subcategoryList']);
    Route::get('/brand-with-products', [CommerceController::class, 'brandWithProducts']);
    Route::get('/brand-wise-products/{brand_id}', [CommerceController::class, 'brandWiseProducts']);
    Route::get('/categories-with-products', [CommerceController::class, 'categoriesWithProducts']);
    Route::get('/category-wise-products/{category_id}', [CommerceController::class, 'categoryWiseProducts']);
    Route::get('/items-sections', [CommerceController::class, 'productSections']);
    Route::get('/recommended-products', [CommerceController::class, 'recommendedProducts']);
    Route::get('/deal-of-the-day', [CommerceController::class, 'dealOfTheDay']);
    Route::get('/products/{product_id}/reviews', [OrderController::class, 'getReviews']);

    // Banner - Public
    Route::get('/flash-banners', [BannerController::class, 'flashBanners']);
    Route::get('/flash-banners/{id}', [BannerController::class, 'flashBannerDetail']);
    Route::get('/banners', [BannerController::class, 'banners']);
    Route::get('/banners/{id}', [BannerController::class, 'bannerDetail']);

    // Division, District, City - Public
    Route::get('/division/list', [ConfigController::class, 'divisionList'])->middleware('optional.auth');
    Route::get('/district/list', [ConfigController::class, 'districtList'])->middleware('optional.auth');
    Route::get('/city/list', [ConfigController::class, 'cityList'])->middleware('optional.auth');

    // Payment Callbacks - Public
    Route::get('/payment/success', [PaymentController::class, 'paymentSuccess']);
    Route::get('/payment/failed', [PaymentController::class, 'paymentFailed']);
    Route::get('/payment/cancel', [PaymentController::class, 'paymentCancel']);



    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'userInfo']);
        Route::match(['put', 'post'], '/auth/user', [AuthController::class, 'updateProfile'])
            ->middleware('parse.multipart.put');
        Route::put('/auth/update-fcm-token', [AuthController::class, 'updateFcmToken']);
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
        Route::get('/favourites', [CommerceController::class, 'favouriteProducts']);
        Route::post('/favourites/add/{item_id}', [CommerceController::class, 'addFavourite']);
        Route::delete('/favourites/remove/{item_id}', [CommerceController::class, 'removeFavourite']);
        Route::get('/notifications', [CommerceController::class, 'notifications']);
        
        // Order routes
        Route::post('/order/cart', [OrderController::class, 'addToCart']);
        Route::put('/order/cart/{cart_id}', [OrderController::class, 'updateCart']);
        Route::get('/order/cart', [OrderController::class, 'cartList']);
        Route::delete('/order/cart/{item_id}', [OrderController::class, 'removeFromCart']);
        Route::post('/order/checkout', [OrderController::class, 'checkout']);
        Route::match(['post', 'delete'], '/order/cancel/{order_id}', [OrderController::class, 'cancelOrder']);
        Route::get('/order/info/{order_id}', [OrderController::class, 'orderInfoDetail']);
        Route::get('/order/info', [OrderController::class, 'ordersByStatus']);
        Route::post('/order/apply-coupon', [OrderController::class, 'applyCoupon']);
        Route::post('/order/reviews/post', [OrderController::class, 'postReview']);
        
        // Payment routes
        Route::post('/payment/init', [PaymentController::class, 'initPayment']);
        Route::post('/payment/complete', [PaymentController::class, 'completePayment']);

    });
});

//need admin api files call
// Include Admin Routes
Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum'], function () {
    require base_path('routes/admin.php');
});
