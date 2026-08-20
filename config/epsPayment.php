<?php

// EPS Payment Gateway Configuration
// Documentation: https://github.com/EPS-PG/EPS_Laravel

return [
    'apiCredentials' => [
        'EPSUserName'     => env('EPSUserName'),
        'EPSPassword'     => env('EPSPassword'),
        'EPSDeviceTypeID' => env('EPSDeviceTypeID', 1),
        'EPSHashkey'      => env('EPSHashkey'),
        'EPSMerchentID'   => env('EPSMerchentID'),
        'EPSStoreID'      => env('EPSStoreID'),
    ],

    'EPSBaseURL' => env('EPSBaseURL'),

    'apiUrl' => [
        'GetToken'           => '/v1/Auth/GetToken',
        'Initialize'         => '/v1/EPSEngine/InitializeEPS',
        'CheckPaymentStatus' => '/v1/EPSEngine/CheckMerchantTransactionStatus',
    ],

    // Frontend redirect URLs (after payment completes, redirect user here)
    'frontend' => [
        'success_url' => env('EPS_FRONTEND_SUCCESS_URL', '/payment/success'),
        'fail_url'    => env('EPS_FRONTEND_FAIL_URL', '/payment/failed'),
        'cancel_url'  => env('EPS_FRONTEND_CANCEL_URL', '/payment/cancel'),
    ],
];
