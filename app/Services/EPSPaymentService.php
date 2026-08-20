<?php

namespace App\Services;

use App\Models\EpsTransaction;
use App\Models\PaymentInfo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * EPSPaymentService
 * 
 * Advanced, safe EPS Payment Gateway integration with:
 * 1. DB Transactions — All operations are atomic
 * 2. Deduplication — Idempotency key prevents duplicate payments
 * 3. Internal Transaction ID Storage — Full audit trail in eps_transactions
 * 4. Server-Side Verification — Never trust callbacks, always verify with EPS API
 * 5. Status Machine — Valid transitions enforced at model level
 */
class EPSPaymentService
{
    protected array $config;
    protected string $baseUrl;
    protected string $userName;
    protected string $password;
    protected string $deviceTypeId;
    protected string $hashkey;
    protected string $merchantId;
    protected string $storeId;

    public function __construct()
    {
        $this->config = config('epsPayment');

        $this->baseUrl      = $this->config['EPSBaseURL'] ?? '';
        $this->userName     = $this->config['apiCredentials']['EPSUserName'] ?? '';
        $this->password     = $this->config['apiCredentials']['EPSPassword'] ?? '';
        $this->deviceTypeId = $this->config['apiCredentials']['EPSDeviceTypeID'] ?? '1';
        $this->hashkey      = $this->config['apiCredentials']['EPSHashkey'] ?? '';
        $this->merchantId   = $this->config['apiCredentials']['EPSMerchentID'] ?? '';
        $this->storeId      = $this->config['apiCredentials']['EPSStoreID'] ?? '';
    }

    // ═══════════════════════════════════════════════════════════
    //  CORE: Initialize Payment (with Deduplication & DB Transaction)
    // ═══════════════════════════════════════════════════════════

    /**
     * Initialize an EPS payment with full safety.
     * 
     * @param string $idempotencyKey  Client-generated UUID for deduplication
     * @param float  $amount          Payment amount
     * @param array  $customerInfo    [name, email, phone, address, city, state, postcode, country]
     * @param array  $products        Array of product items [{ProductName, NoOfItem, ProductProfile, ProductCategory, ProductPrice}]
     * @param array  $orderInfo       [order_id, shipping_method, customer_order_id]
     * @param int|null $userId        Authenticated user ID
     * @param string|null $ipAddress  Client IP address
     * @param string|null $userAgent  Client user agent
     * 
     * @return array ['success' => bool, 'redirect_url' => string|null, 'eps_transaction' => EpsTransaction, 'message' => string]
     */
    public function initializePayment(
        string $idempotencyKey,
        float  $amount,
        array  $customerInfo = [],
        array  $products = [],
        array  $orderInfo = [],
        ?int   $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): array {
        // ─── Step 1: Deduplication Check ───────────────────────
        $existingTransaction = EpsTransaction::byIdempotencyKey($idempotencyKey)->first();

        if ($existingTransaction) {
            Log::info("EPS: Duplicate request detected", [
                'idempotency_key' => $idempotencyKey,
                'existing_status' => $existingTransaction->status,
                'merchant_txn_id' => $existingTransaction->merchant_transaction_id,
            ]);

            // If it's already pending or beyond, return the existing redirect URL
            if ($existingTransaction->status !== EpsTransaction::STATUS_FAILED) {
                return [
                    'success'         => true,
                    'redirect_url'    => $existingTransaction->redirect_url,
                    'eps_transaction'  => $existingTransaction,
                    'message'         => 'Payment already initiated (deduplication)',
                    'is_duplicate'    => true,
                ];
            }
            // If the previous attempt failed, allow retry with new merchant_transaction_id
            // We'll fall through and create a new transaction below
        }

        // ─── Step 2: Validate Configuration ────────────────────
        $this->validateConfig();

        // ─── Step 3: Generate Merchant Transaction ID ──────────
        $merchantTransactionId = $this->generateMerchantTransactionId();

        // ─── Step 4: Atomic DB Transaction ─────────────────────
        return DB::transaction(function () use (
            $idempotencyKey, $amount, $customerInfo, $products, $orderInfo,
            $userId, $ipAddress, $userAgent, $merchantTransactionId
        ) {
            // 4a. Create EPS Transaction record (status = initiated)
            $epsTxn = EpsTransaction::create([
                'idempotency_key'          => $idempotencyKey,
                'merchant_transaction_id'  => $merchantTransactionId,
                'order_id'                 => $orderInfo['order_id'] ?? null,
                'user_id'                  => $userId,
                'amount'                   => $amount,
                'currency'                 => 'BDT',
                'status'                   => EpsTransaction::STATUS_INITIATED,
                'customer_name'            => $customerInfo['name'] ?? null,
                'customer_email'           => $customerInfo['email'] ?? null,
                'customer_phone'           => $customerInfo['phone'] ?? null,
                'ip_address'               => $ipAddress,
                'user_agent'               => $userAgent,
                'initiated_at'             => now(),
            ]);

            // 4b. Create PaymentInfo record (status = init)
            $payment = PaymentInfo::create([
                'user_full_name'    => $customerInfo['name'] ?? '',
                'user_email'        => $customerInfo['email'] ?? '',
                'user_phone'        => $customerInfo['phone'] ?? '',
                'payment_amount'    => $amount,
                'payment_gateway'   => 'eps',
                'transaction_id'    => $merchantTransactionId,
                'status'            => PaymentInfo::STATUS_INIT,
                'created_by'        => $userId,
            ]);

            $epsTxn->payment_info_id = $payment->id;
            $epsTxn->save();

            // 4c. Get EPS Bearer Token
            $tokenResponse = $this->getToken();
            if (!$tokenResponse['success']) {
                $epsTxn->transitionTo(EpsTransaction::STATUS_FAILED);
                $epsTxn->eps_response_payload = $tokenResponse;
                $epsTxn->save();

                $payment->status = PaymentInfo::STATUS_FAILED;
                $payment->gateway_response = $tokenResponse;
                $payment->save();

                Log::error("EPS: Token generation failed", ['response' => $tokenResponse]);

                return [
                    'success'        => false,
                    'redirect_url'   => null,
                    'eps_transaction' => $epsTxn,
                    'message'        => 'EPS authentication failed: ' . ($tokenResponse['error'] ?? 'Unknown error'),
                ];
            }

            $token = $tokenResponse['token'];

            // 4d. Build EPS Initialize Payload
            $epsPayload = $this->buildInitializePayload(
                $merchantTransactionId, $amount, $customerInfo, $products, $orderInfo
            );

            // Store the full request payload for audit
            $epsTxn->eps_request_payload = $epsPayload;
            $epsTxn->save();

            // 4e. Call EPS Initialize API
            $initResponse = $this->callEpsInitialize($epsPayload, $merchantTransactionId, $token);

            // Store the full response payload for audit
            $epsTxn->eps_response_payload = $initResponse;
            $epsTxn->save();

            $redirectUrl = $initResponse['RedirectURL'] ?? $initResponse['redirectURL'] ?? $initResponse['redirectUrl'] ?? $initResponse['url'] ?? null;

            if ($initResponse['isSuccess'] && !empty($redirectUrl)) {
                // Success — got redirect URL
                $epsTxn->redirect_url = $redirectUrl;
                $epsTxn->transitionTo(EpsTransaction::STATUS_PENDING);
                $epsTxn->save();

                $payment->status = PaymentInfo::STATUS_PENDING;
                $payment->save();

                Log::info("EPS: Payment initialized successfully", [
                    'merchant_txn_id' => $merchantTransactionId,
                    'redirect_url'    => $redirectUrl,
                ]);

                return [
                    'success'        => true,
                    'redirect_url'   => $redirectUrl,
                    'eps_transaction' => $epsTxn->fresh(),
                    'message'        => 'Payment initialized successfully',
                    'is_duplicate'   => false,
                ];
            } else {
                // Failed — EPS rejected the payment
                $epsTxn->transitionTo(EpsTransaction::STATUS_FAILED);
                $epsTxn->save();

                $payment->status = PaymentInfo::STATUS_FAILED;
                $payment->gateway_response = $initResponse;
                $payment->save();

                $errorMsg = $initResponse['ErrorMessage'] ?? $initResponse['message'] ?? 'EPS initialization failed';

                Log::error("EPS: Payment initialization failed", [
                    'merchant_txn_id' => $merchantTransactionId,
                    'response'        => $initResponse,
                ]);

                return [
                    'success'        => false,
                    'redirect_url'   => null,
                    'eps_transaction' => $epsTxn,
                    'message'        => $errorMsg,
                ];
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    //  CALLBACK: Handle EPS Redirect Callbacks (Success/Fail/Cancel)
    // ═══════════════════════════════════════════════════════════

    /**
     * Handle EPS callback (success, fail, or cancel redirect).
     * 
     * CRITICAL: This method performs SERVER-SIDE verification.
     * We NEVER trust the callback params alone.
     * 
     * @param array  $callbackParams  GET parameters from EPS redirect
     * @param string $callbackType    'success', 'fail', or 'cancel'
     * 
     * @return array ['success' => bool, 'eps_transaction' => EpsTransaction|null, 'message' => string]
     */
    public function handleCallback(array $callbackParams, string $callbackType): array
    {
        $merchantTransactionId = $callbackParams['MerchantTransactionId'] ?? $callbackParams['merchantTransactionId'] ?? null;
        $epsTransactionId      = $callbackParams['EPSTransactionId_'] ?? $callbackParams['TransactionId'] ?? null;
        $callbackStatus        = $callbackParams['Status'] ?? null;

        Log::info("EPS: Callback received", [
            'type'              => $callbackType,
            'merchant_txn_id'   => $merchantTransactionId,
            'eps_txn_id'        => $epsTransactionId,
            'status'            => $callbackStatus,
            'all_params'        => $callbackParams,
        ]);

        if (!$merchantTransactionId) {
            Log::error("EPS: Callback missing MerchantTransactionId", $callbackParams);
            return [
                'success'        => false,
                'eps_transaction' => null,
                'message'        => 'Missing MerchantTransactionId in callback',
            ];
        }

        // ─── Atomic DB Transaction for Callback Processing ────
        return DB::transaction(function () use ($merchantTransactionId, $epsTransactionId, $callbackParams, $callbackType) {

            // Find the EPS transaction with pessimistic lock
            $epsTxn = EpsTransaction::byMerchantTransactionId($merchantTransactionId)
                ->lockForUpdate()
                ->first();

            if (!$epsTxn) {
                Log::error("EPS: Transaction not found for callback", [
                    'merchant_txn_id' => $merchantTransactionId,
                ]);
                return [
                    'success'        => false,
                    'eps_transaction' => null,
                    'message'        => 'Transaction not found',
                ];
            }

            // Skip if already in terminal state (prevent replay attacks)
            if ($epsTxn->isTerminal()) {
                Log::warning("EPS: Callback received for terminal transaction", [
                    'merchant_txn_id' => $merchantTransactionId,
                    'current_status'  => $epsTxn->status,
                ]);
                return [
                    'success'        => $epsTxn->status === EpsTransaction::STATUS_COMPLETED,
                    'eps_transaction' => $epsTxn,
                    'message'        => "Transaction already in terminal state: {$epsTxn->status}",
                ];
            }

            // Store callback payload
            $epsTxn->eps_callback_payload = $callbackParams;
            if ($epsTransactionId) {
                $epsTxn->eps_transaction_id = $epsTransactionId;
            }
            $epsTxn->save();

            // ─── Handle based on callback type ─────────────────
            if ($callbackType === 'success') {
                return $this->processSuccessCallback($epsTxn, $merchantTransactionId);
            } elseif ($callbackType === 'cancel') {
                return $this->processCancelCallback($epsTxn);
            } else {
                return $this->processFailCallback($epsTxn);
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    //  VERIFY: Standalone Payment Verification
    // ═══════════════════════════════════════════════════════════

    /**
     * Verify a payment status with EPS API (server-side).
     * Can be called independently for manual verification.
     * 
     * @param string $merchantTransactionId
     * @return array
     */
    public function verifyPayment(string $merchantTransactionId): array
    {
        $epsTxn = EpsTransaction::byMerchantTransactionId($merchantTransactionId)->first();

        if (!$epsTxn) {
            return ['success' => false, 'message' => 'Transaction not found'];
        }

        return DB::transaction(function () use ($epsTxn, $merchantTransactionId) {
            // Re-fetch with lock
            $epsTxn = EpsTransaction::byMerchantTransactionId($merchantTransactionId)
                ->lockForUpdate()
                ->first();

            $verificationResult = $this->serverSideVerify($merchantTransactionId);

            $epsTxn->eps_verification_payload = $verificationResult;
            $epsTxn->verification_attempts = ($epsTxn->verification_attempts ?? 0) + 1;
            $epsTxn->save();

            // Update PaymentInfo
            $payment = $epsTxn->paymentInfo;

            if ($verificationResult['verified']) {
                if ($epsTxn->canTransitionTo(EpsTransaction::STATUS_VERIFIED)) {
                    $epsTxn->transitionTo(EpsTransaction::STATUS_VERIFIED);
                    $epsTxn->save();
                }

                if ($payment && $payment->status !== PaymentInfo::STATUS_COMPLETE) {
                    $payment->status = PaymentInfo::STATUS_COMPLETE;
                    $payment->bank_transaction_id = $verificationResult['eps_transaction_id'] ?? null;
                    $payment->gateway_response = $verificationResult;
                    $payment->save();
                }

                // Update Order payment_status to 'paid'
                $order = null;
                if ($epsTxn->order_id) {
                    $order = \App\Models\Order::find($epsTxn->order_id);
                }
                if (!$order) {
                    $order = \App\Models\Order::where('transaction_id', $merchantTransactionId)->first();
                }
                if (!$order && $payment) {
                    $order = \App\Models\Order::where('payment_id', $payment->id)->first();
                }

                if ($order) {
                    $order->payment_status = 'paid';
                    $order->transaction_id = $merchantTransactionId;
                    if ($payment) {
                        $order->payment_id = $payment->id;
                    }
                    $order->save();
                }

                return [
                    'success'        => true,
                    'eps_transaction' => $epsTxn->fresh(),
                    'message'        => 'Payment verified successfully',
                    'verification'   => $verificationResult,
                ];
            } else {
                if ($epsTxn->canTransitionTo(EpsTransaction::STATUS_FAILED)) {
                    $epsTxn->transitionTo(EpsTransaction::STATUS_FAILED);
                    $epsTxn->save();
                }

                if ($payment && !in_array($payment->status, [PaymentInfo::STATUS_COMPLETE])) {
                    $payment->status = PaymentInfo::STATUS_FAILED;
                    $payment->gateway_response = $verificationResult;
                    $payment->save();
                }

                return [
                    'success'        => false,
                    'eps_transaction' => $epsTxn->fresh(),
                    'message'        => 'Payment verification failed',
                    'verification'   => $verificationResult,
                ];
            }
        });
    }

    // ═══════════════════════════════════════════════════════════
    //  INTERNAL: EPS API Communication
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate HMAC-SHA512 hash for EPS API authentication.
     */
    protected function generateHash(string $payload, string $hashkey): string
    {
        $utf8Key     = mb_convert_encoding($hashkey, 'UTF-8');
        $utf8Payload = mb_convert_encoding($payload, 'UTF-8');
        $data        = hash_hmac('sha512', $utf8Payload, $utf8Key, true);
        return base64_encode($data);
    }

    /**
     * Safely construct endpoint URL without double slash or outdated route issues.
     */
    protected function buildUrl(string $path): string
    {
        $base = rtrim($this->baseUrl ?: 'https://pgapi.eps.com.bd', '/');

        // Map legacy/outdated package paths to active OpenAPI v1 endpoints
        $legacyMap = [
            '/api/account/login'        => '/v1/Auth/GetToken',
            'api/account/login'         => '/v1/Auth/GetToken',
            '/api/payment/initialize'   => '/v1/EPSEngine/InitializeEPS',
            'api/payment/initialize'    => '/v1/EPSEngine/InitializeEPS',
            '/api/payment/checkStatus/' => '/v1/EPSEngine/CheckMerchantTransactionStatus',
            'api/payment/checkStatus/'  => '/v1/EPSEngine/CheckMerchantTransactionStatus',
        ];

        $cleanPath = $legacyMap[$path] ?? $path;
        return $base . '/' . ltrim($cleanPath, '/');
    }

    /**
     * Get EPS Bearer Token via /v1/Auth/GetToken.
     * 
     * @return array ['success' => bool, 'token' => string|null, 'error' => string|null]
     */
    protected function getToken(): array
    {
        try {
            $xHash = $this->generateHash($this->userName, $this->hashkey);
            $endpoint = $this->buildUrl($this->config['apiUrl']['GetToken'] ?? '/v1/Auth/GetToken');

            $response = Http::withoutVerifying()->timeout(30)
                ->withHeaders([
                    'x-hash'       => $xHash,
                    'Content-Type' => 'application/json',
                ])
                ->post($endpoint, [
                    'userName' => $this->userName,
                    'password' => $this->password,
                ]);

            if ($response->status() === 200 && isset($response->json()['token'])) {
                return [
                    'success' => true,
                    'token'   => $response->json()['token'],
                ];
            }

            Log::error("EPS: Token request failed", [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);

            return [
                'success' => false,
                'token'   => null,
                'error'   => 'EPS authentication failed (status: ' . $response->status() . ')',
                'details' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("EPS: Token request exception", [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'token'   => null,
                'error'   => 'EPS connection error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build the payload for EPS /api/payment/initialize.
     */
    protected function buildInitializePayload(
        string $merchantTransactionId,
        float  $amount,
        array  $customerInfo,
        array  $products,
        array  $orderInfo
    ): array {
        $payload = [
            // Device & Merchant Info
            'deviceTypeId'         => $this->deviceTypeId,
            'merchantId'           => $this->merchantId,
            'storeId'              => $this->storeId,
            'transactionTypeId'    => 1,
            'financialEntityId'    => 0,
            'version'              => '1',
            'transactionDate'      => date('c'),
            'transitionStatusId'   => 0,
            'valueD'               => '',

            // Our Transaction ID
            'merchantTransactionId' => $merchantTransactionId,

            // Amount
            'totalAmount' => (string)$amount,

            // IP Address
            'ipAddress' => $customerInfo['ip'] ?? request()->ip() ?? '127.0.0.1',

            // Customer Order ID (for EPS reference)
            'CustomerOrderId' => $orderInfo['customer_order_id'] ?? $merchantTransactionId,

            // Callback URLs (must match registered domain valokichu.com)
            'successUrl' => env('EPS_SUCCESS_URL', 'https://valokichu.com/payment/success'),
            'failUrl'    => env('EPS_FAIL_URL', 'https://valokichu.com/payment/failed'),
            'cancelUrl'  => env('EPS_CANCEL_URL', 'https://valokichu.com/payment/cancel'),

            // Customer Info
            'customerName'     => $customerInfo['name'] ?? '',
            'customerEmail'    => $customerInfo['email'] ?? '',
            'customerPhone'    => $customerInfo['phone'] ?? '',
            'customerAddress'  => $customerInfo['address'] ?? '',
            'customerAddress2' => $customerInfo['address2'] ?? '',
            'customerCity'     => $customerInfo['city'] ?? '',
            'customerState'    => $customerInfo['state'] ?? '',
            'customerPostcode' => $customerInfo['postcode'] ?? '',
            'customerCountry'  => $customerInfo['country'] ?? 'Bangladesh',

            // Shipment Info (mirror customer info by default)
            'shipmentName'     => $customerInfo['name'] ?? '',
            'shipmentAddress'  => $customerInfo['address'] ?? '',
            'shipmentAddress2' => $customerInfo['address2'] ?? '',
            'shipmentCity'     => $customerInfo['city'] ?? '',
            'shipmentState'    => $customerInfo['state'] ?? '',
            'shipmentPostcode' => $customerInfo['postcode'] ?? '',
            'shipmentCountry'  => $customerInfo['country'] ?? 'Bangladesh',

            // Custom values (store our internal references)
            'valueA' => $orderInfo['order_id'] ?? '',
            'valueB' => $merchantTransactionId,
            'valueC' => (string)($customerInfo['user_id'] ?? ''),

            // Shipping & Product Summary
            'shippingMethod'  => $orderInfo['shipping_method'] ?? 'Home Delivery',
            'noOfItem'        => (string)count($products),
            'productName'     => implode(', ', array_column($products, 'ProductName')),
            'productProfile'  => implode(', ', array_column($products, 'ProductProfile')),
            'productCategory' => implode(', ', array_column($products, 'ProductCategory')),

            // Product List
            'ProductList' => $products,
        ];

        return $payload;
    }

    /**
     * Call EPS /api/payment/initialize with HMAC auth.
     */
    protected function callEpsInitialize(array $payload, string $merchantTransactionId, string $token): array
    {
        try {
            $xHash = $this->generateHash($merchantTransactionId, $this->hashkey);
            $endpoint = $this->buildUrl($this->config['apiUrl']['Initialize'] ?? '/v1/EPSEngine/InitializeEPS');

            $response = Http::withoutVerifying()->timeout(60)
                ->withHeaders([
                    'x-hash'        => $xHash,
                    'Authorization' => "Bearer {$token}",
                    'Content-Type'  => 'application/json',
                ])
                ->post($endpoint, $payload);

            $data = $response->json() ?? [];

            if ($response->status() === 200) {
                $data['isSuccess'] = true;
            } else {
                $data['isSuccess'] = false;
            }

            $data['_http_status'] = $response->status();
            return $data;

        } catch (\Exception $e) {
            Log::error("EPS: Initialize API exception", [
                'merchant_txn_id' => $merchantTransactionId,
                'error'           => $e->getMessage(),
            ]);

            return [
                'isSuccess'    => false,
                'ErrorMessage' => 'EPS API connection error: ' . $e->getMessage(),
                '_http_status' => 0,
            ];
        }
    }

    /**
     * Server-side verification via EPS /api/payment/checkStatus/{id}.
     * 
     * This is the CRITICAL safety check. Never rely on callback params alone.
     * 
     * @return array ['verified' => bool, 'eps_status' => string, 'amount' => string|null, ...]
     */
    protected function serverSideVerify(string $merchantTransactionId): array
    {
        try {
            $tokenResponse = $this->getToken();
            if (!$tokenResponse['success']) {
                return [
                    'verified' => false,
                    'error'    => 'Failed to get token for verification',
                    'details'  => $tokenResponse,
                ];
            }

            $xHash = $this->generateHash($merchantTransactionId, $this->hashkey);
            $token = $tokenResponse['token'];
            $baseApi = $this->buildUrl($this->config['apiUrl']['CheckPaymentStatus'] ?? '/v1/EPSEngine/CheckMerchantTransactionStatus');
            $endpoint = $baseApi . '?merchantTransactionId=' . urlencode($merchantTransactionId);

            $response = Http::withoutVerifying()->timeout(30)
                ->withHeaders([
                    'x-hash'        => $xHash,
                    'Authorization' => "Bearer {$token}",
                    'Content-Type'  => 'application/json',
                ])
                ->get($endpoint);

            $data = $response->json() ?? [];

            Log::info("EPS: Server-side verification response", [
                'merchant_txn_id' => $merchantTransactionId,
                'status_code'     => $response->status(),
                'data'            => $data,
            ]);

            // Determine if the payment is truly successful
            // EPS returns "Status": "Success"
            $epsStatus = $data['Status'] ?? $data['status'] ?? $data['transactionStatus'] ?? null;
            $isVerified = $response->status() === 200 && 
                          in_array(strtolower((string)$epsStatus), ['success', 'completed', 'valid', '1', 'true']);

            return [
                'verified'           => $isVerified,
                'eps_status'         => $epsStatus,
                'eps_transaction_id' => $data['EPSTransactionId'] ?? $data['TransactionId'] ?? null,
                'amount'             => $data['TotalAmount'] ?? $data['Amount'] ?? $data['amount'] ?? $data['totalAmount'] ?? null,
                'raw_response'       => $data,
                '_http_status'       => $response->status(),
            ];

        } catch (\Exception $e) {
            Log::error("EPS: Server-side verification exception", [
                'merchant_txn_id' => $merchantTransactionId,
                'error'           => $e->getMessage(),
            ]);

            return [
                'verified' => false,
                'error'    => 'Verification API error: ' . $e->getMessage(),
            ];
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  INTERNAL: Callback Processing Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Process a success callback with server-side verification.
     */
    protected function processSuccessCallback(EpsTransaction $epsTxn, string $merchantTransactionId): array
    {
        // CRITICAL: Server-side verification before trusting success callback
        $verificationResult = $this->serverSideVerify($merchantTransactionId);

        $epsTxn->eps_verification_payload = $verificationResult;
        $epsTxn->verification_attempts = ($epsTxn->verification_attempts ?? 0) + 1;
        $epsTxn->save();

        $payment = $epsTxn->paymentInfo;

        if ($verificationResult['verified']) {
            // Amount verification (prevent amount tampering)
            $verifiedAmount = $verificationResult['amount'] ?? null;
            if ($verifiedAmount !== null && abs((float)$verifiedAmount - (float)$epsTxn->amount) > 0.01) {
                Log::critical("EPS: AMOUNT MISMATCH DETECTED!", [
                    'merchant_txn_id'  => $merchantTransactionId,
                    'expected_amount'  => $epsTxn->amount,
                    'verified_amount'  => $verifiedAmount,
                ]);

                $epsTxn->transitionTo(EpsTransaction::STATUS_FAILED);
                $epsTxn->save();

                if ($payment) {
                    $payment->status = PaymentInfo::STATUS_FAILED;
                    $payment->gateway_response = [
                        'error' => 'Amount mismatch',
                        'expected' => $epsTxn->amount,
                        'received' => $verifiedAmount,
                    ];
                    $payment->save();
                }

                return [
                    'success'        => false,
                    'eps_transaction' => $epsTxn,
                    'message'        => 'Payment amount mismatch — potential tampering detected',
                ];
            }

            // Verification passed!
            $epsTxn->eps_transaction_id = $verificationResult['eps_transaction_id'] ?? $epsTxn->eps_transaction_id;
            $epsTxn->transitionTo(EpsTransaction::STATUS_VERIFIED);
            $epsTxn->save();

            if ($payment) {
                $payment->status = PaymentInfo::STATUS_COMPLETE;
                $payment->bank_transaction_id = $verificationResult['eps_transaction_id'] ?? null;
                $payment->gateway_response = $verificationResult;
                $payment->save();
            }

            // Update associated Order model payment_status to 'paid'
            $order = null;
            if ($epsTxn->order_id) {
                $order = \App\Models\Order::find($epsTxn->order_id);
            }
            if (!$order) {
                $order = \App\Models\Order::where('transaction_id', $merchantTransactionId)->first();
            }
            if (!$order && $payment) {
                $order = \App\Models\Order::where('payment_id', $payment->id)->first();
            }

            if ($order) {
                $order->payment_status = 'paid';
                $order->transaction_id = $merchantTransactionId;
                if ($payment) {
                    $order->payment_id = $payment->id;
                }
                $order->save();
                Log::info("EPS: Order {$order->id} payment_status updated to paid");
            }

            Log::info("EPS: Payment verified and completed", [
                'merchant_txn_id' => $merchantTransactionId,
                'eps_txn_id'      => $epsTxn->eps_transaction_id,
            ]);

            return [
                'success'        => true,
                'eps_transaction' => $epsTxn->fresh(),
                'message'        => 'Payment verified successfully',
            ];
        } else {
            // Verification failed — the callback may be spoofed
            Log::warning("EPS: Success callback received but server-side verification FAILED", [
                'merchant_txn_id' => $merchantTransactionId,
                'verification'    => $verificationResult,
            ]);

            // Don't fail immediately — might be a timing issue
            // Keep status as pending for manual review
            return [
                'success'        => false,
                'eps_transaction' => $epsTxn,
                'message'        => 'Payment verification pending — server-side check inconclusive',
            ];
        }
    }

    /**
     * Process a fail callback.
     */
    protected function processFailCallback(EpsTransaction $epsTxn): array
    {
        $epsTxn->transitionTo(EpsTransaction::STATUS_FAILED);
        $epsTxn->save();

        $payment = $epsTxn->paymentInfo;
        if ($payment) {
            $payment->status = PaymentInfo::STATUS_FAILED;
            $payment->save();
        }

        Log::info("EPS: Payment failed", [
            'merchant_txn_id' => $epsTxn->merchant_transaction_id,
        ]);

        return [
            'success'        => false,
            'eps_transaction' => $epsTxn,
            'message'        => 'Payment failed',
        ];
    }

    /**
     * Process a cancel callback.
     */
    protected function processCancelCallback(EpsTransaction $epsTxn): array
    {
        $epsTxn->transitionTo(EpsTransaction::STATUS_CANCELLED);
        $epsTxn->save();

        $payment = $epsTxn->paymentInfo;
        if ($payment) {
            $payment->status = PaymentInfo::STATUS_CANCELLED;
            $payment->save();
        }

        Log::info("EPS: Payment cancelled by user", [
            'merchant_txn_id' => $epsTxn->merchant_transaction_id,
        ]);

        return [
            'success'        => false,
            'eps_transaction' => $epsTxn,
            'message'        => 'Payment cancelled by user',
        ];
    }

    // ═══════════════════════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate a unique merchant transaction ID.
     * Format: EPS_<timestamp>_<random> (unique, sortable, traceable)
     */
    protected function generateMerchantTransactionId(): string
    {
        return 'EPS_' . Carbon::now()->format('YmdHis') . '_' . strtoupper(Str::random(6));
    }

    /**
     * Validate that all required EPS config values are present.
     * 
     * @throws \RuntimeException
     */
    protected function validateConfig(): void
    {
        $required = ['baseUrl', 'userName', 'password', 'hashkey', 'merchantId', 'storeId'];

        foreach ($required as $field) {
            if (empty($this->{$field})) {
                throw new \RuntimeException(
                    "EPS Payment Gateway configuration error: '{$field}' is missing or empty. " .
                    "Check your .env file and config/epsPayment.php"
                );
            }
        }
    }
}
