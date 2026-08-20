<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EpsTransaction;
use App\Models\PaymentInfo;
use App\Services\EPSPaymentService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // ═══════════════════════════════════════════════════════════
    //  EXISTING: Generic Payment Init
    // ═══════════════════════════════════════════════════════════

    public function initPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|string|in:offline_payment,bkash,nagad,stripe,sslcommerz,eps',
            'amount' => 'required|numeric|min:0',
            'user_name' => 'required|string',
            'user_email' => 'required|email',
            'user_phone' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();

        try {
            $result = $this->paymentService->createPayment(
                $request->gateway,
                $request->amount,
                $request->user_name,
                $request->user_email,
                $request->user_phone,
                $user
            );

            return response()->json($result['data'], $result['status']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EPS: Initialize Payment (Advanced with Deduplication)
    // ═══════════════════════════════════════════════════════════

    /**
     * Initialize an EPS payment.
     * 
     * Accepts an idempotency_key to prevent duplicate payments.
     * Returns a redirect URL to the EPS payment page.
     * 
     * POST /api/v1/payment/init-eps
     */
    public function initEpsPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idempotency_key' => 'required|uuid',
            'amount'          => 'required|numeric|min:1',
            'customer_name'   => 'required|string|max:255',
            'customer_email'  => 'nullable|email|max:255',
            'customer_phone'  => 'required|string|max:20',
            'customer_address' => 'nullable|string|max:500',
            'customer_city'   => 'nullable|string|max:100',
            'customer_state'  => 'nullable|string|max:100',
            'customer_postcode' => 'nullable|string|max:20',
            'products'        => 'nullable|array',
            'products.*.ProductName'     => 'required_with:products|string',
            'products.*.NoOfItem'        => 'required_with:products|string',
            'products.*.ProductProfile'  => 'nullable|string',
            'products.*.ProductCategory' => 'nullable|string',
            'products.*.ProductPrice'    => 'required_with:products|string',
            'order_id'        => 'nullable|integer|exists:orders,id',
            'shipping_method' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'VALIDATION_ERROR',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            $epsService = app(EPSPaymentService::class);

            $result = $epsService->initializePayment(
                idempotencyKey: $request->idempotency_key,
                amount: (float) $request->amount,
                customerInfo: [
                    'name'     => $request->customer_name,
                    'email'    => $request->customer_email ?? '',
                    'phone'    => $request->customer_phone,
                    'address'  => $request->customer_address ?? '',
                    'city'     => $request->customer_city ?? '',
                    'state'    => $request->customer_state ?? '',
                    'postcode' => $request->customer_postcode ?? '',
                    'country'  => 'Bangladesh',
                    'ip'       => $request->ip(),
                    'user_id'  => $user?->id,
                ],
                products: $request->products ?? [],
                orderInfo: [
                    'order_id'          => $request->order_id,
                    'shipping_method'   => $request->shipping_method ?? 'Home Delivery',
                    'customer_order_id' => $request->order_id ? "ORD-{$request->order_id}" : null,
                ],
                userId: $user?->id,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            $statusCode = $result['success'] ? 201 : 400;

            return response()->json([
                'status'                  => $result['success'] ? 'SUCCESS' : 'FAILED',
                'message'                 => $result['message'],
                'redirect_url'            => $result['redirect_url'] ?? null,
                'merchant_transaction_id' => $result['eps_transaction']->merchant_transaction_id ?? null,
                'idempotency_key'         => $request->idempotency_key,
                'is_duplicate'            => $result['is_duplicate'] ?? false,
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error("EPS: initEpsPayment controller exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EPS: Callback Handlers (Success / Fail / Cancel)
    // ═══════════════════════════════════════════════════════════

    /**
     * EPS Success Callback.
     * 
     * GET /api/v1/eps/success?MerchantTransactionId=X&Status=Y
     * 
     * This is called by EPS via browser redirect after successful payment.
     * We ALWAYS verify server-side before trusting this callback.
     */
    public function epsSuccess(Request $request)
    {
        Log::info("EPS: Success callback hit", ['params' => $request->all()]);

        try {
            $epsService = app(EPSPaymentService::class);
            $result = $epsService->handleCallback($request->all(), 'success');

            $epsTxn = $result['eps_transaction'] ?? null;

            // Redirect to frontend with transaction details
            $frontendUrl = config('epsPayment.frontend.success_url', '/payment/success');
            $queryParams = http_build_query([
                'merchant_transaction_id' => $epsTxn?->merchant_transaction_id ?? '',
                'status'                  => $result['success'] ? 'verified' : 'pending',
                'payment_id'              => $epsTxn?->payment_info_id ?? '',
            ]);

            // If it's an API-style request, return JSON
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => $result['success'] ? 'SUCCESS' : 'PENDING',
                    'message' => $result['message'],
                    'merchant_transaction_id' => $epsTxn?->merchant_transaction_id,
                    'payment_status'          => $epsTxn?->status,
                ], $result['success'] ? 200 : 202);
            }

            return redirect($frontendUrl . '?' . $queryParams);

        } catch (\Exception $e) {
            Log::error("EPS: Success callback exception", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }

    /**
     * EPS Fail Callback.
     * 
     * GET /api/v1/eps/fail?EPSTransactionId_=X&Status=Y
     */
    public function epsFail(Request $request)
    {
        Log::info("EPS: Fail callback hit", ['params' => $request->all()]);

        try {
            $epsService = app(EPSPaymentService::class);
            $result = $epsService->handleCallback($request->all(), 'fail');

            $epsTxn = $result['eps_transaction'] ?? null;

            $frontendUrl = config('epsPayment.frontend.fail_url', '/payment/failed');
            $queryParams = http_build_query([
                'merchant_transaction_id' => $epsTxn?->merchant_transaction_id ?? '',
                'status'                  => 'failed',
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'FAILED',
                    'message' => $result['message'],
                    'merchant_transaction_id' => $epsTxn?->merchant_transaction_id,
                ], 200);
            }

            return redirect($frontendUrl . '?' . $queryParams);

        } catch (\Exception $e) {
            Log::error("EPS: Fail callback exception", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }

    /**
     * EPS Cancel Callback.
     * 
     * GET /api/v1/eps/cancel?EPSTransactionId_=X&Status=Y
     */
    public function epsCancel(Request $request)
    {
        Log::info("EPS: Cancel callback hit", ['params' => $request->all()]);

        try {
            $epsService = app(EPSPaymentService::class);
            $result = $epsService->handleCallback($request->all(), 'cancel');

            $epsTxn = $result['eps_transaction'] ?? null;

            $frontendUrl = config('epsPayment.frontend.cancel_url', '/payment/cancel');
            $queryParams = http_build_query([
                'merchant_transaction_id' => $epsTxn?->merchant_transaction_id ?? '',
                'status'                  => 'cancelled',
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => 'CANCELLED',
                    'message' => $result['message'],
                    'merchant_transaction_id' => $epsTxn?->merchant_transaction_id,
                ], 200);
            }

            return redirect($frontendUrl . '?' . $queryParams);

        } catch (\Exception $e) {
            Log::error("EPS: Cancel callback exception", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Callback processing failed'], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EPS: Manual Verification
    // ═══════════════════════════════════════════════════════════

    /**
     * Manually verify/check an EPS payment status.
     * 
     * POST /api/v1/payment/verify-eps
     */
    public function verifyEpsPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'merchant_transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $epsService = app(EPSPaymentService::class);
            $result = $epsService->verifyPayment($request->merchant_transaction_id);

            return response()->json([
                'status'                  => $result['success'] ? 'VERIFIED' : 'UNVERIFIED',
                'message'                 => $result['message'],
                'merchant_transaction_id' => $request->merchant_transaction_id,
                'payment_status'          => $result['eps_transaction']->status ?? null,
                'verification'            => $result['verification'] ?? null,
            ], $result['success'] ? 200 : 400);

        } catch (\Exception $e) {
            Log::error("EPS: Verification controller exception", ['error' => $e->getMessage()]);
            return response()->json([
                'status'  => 'ERROR',
                'message' => 'Verification failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ═══════════════════════════════════════════════════════════
    //  EXISTING: Payment Callbacks (non-EPS)
    // ═══════════════════════════════════════════════════════════

    public function paymentSuccess(Request $request)
    {
        $tranId = $request->get('tran_id') ?? $request->post('tran_id');
        
        if ($tranId) {
            $payment = PaymentInfo::where('transaction_id', $tranId)->first();
            if ($payment) {
                $payment->status = PaymentInfo::STATUS_COMPLETE;
                $payment->save();
            }
        }

        return view('payment_success', ['tran_id' => $tranId]);
    }

    public function paymentFailed(Request $request)
    {
        $tranId = $request->get('tran_id') ?? $request->post('tran_id');
        
        if ($tranId) {
            $payment = PaymentInfo::where('transaction_id', $tranId)->first();
            if ($payment) {
                $payment->status = PaymentInfo::STATUS_FAILED;
                $payment->save();
            }
        }

        return view('payment_failed', ['tran_id' => $tranId]);
    }

    public function paymentCancel(Request $request)
    {
        $tranId = $request->get('tran_id') ?? $request->post('tran_id');
        
        if ($tranId) {
            $payment = PaymentInfo::where('transaction_id', $tranId)->first();
            if ($payment) {
                $payment->status = PaymentInfo::STATUS_CANCELLED;
                $payment->save();
            }
        }

        return view('payment_cancel', ['tran_id' => $tranId]);
    }

    public function completePayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tran_id' => 'required_without:transaction_id|string',
            'transaction_id' => 'required_without:tran_id|string',
            'products' => 'required|array',
            'payment_method' => 'required|string',
            'address_id' => 'required|exists:address,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Authentication required'], 401);
        }

        $tranId = $request->tran_id ?? $request->transaction_id;
        $payment = PaymentInfo::where('transaction_id', $tranId)->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if ($payment->status !== PaymentInfo::STATUS_COMPLETE) {
            return response()->json([
                'status' => $payment->status,
                'message' => 'Payment Not Valid or Payment Failed',
                'payment' => $payment
            ], 400);
        }

        // Call checkout
        $checkoutController = new OrderController();
        $checkoutRequest = Request::create('/api/order/checkout', 'POST', [
            'products' => $request->products,
            'payment_method' => $request->payment_method,
            'address_id' => $request->address_id,
            'tran_id' => $tranId,
        ]);
        $checkoutRequest->setUserResolver(function() use ($user) {
            return $user;
        });

        $checkoutResponse = $checkoutController->checkout($checkoutRequest);

        if ($checkoutResponse->getStatusCode() !== 201) {
            return response()->json($checkoutResponse->getData(), $checkoutResponse->getStatusCode());
        }

        return response()->json([
            'message' => 'Payment Successfully',
            'status' => $payment->status,
            'payment' => $payment,
            'order' => $checkoutResponse->getData()
        ], 201);
    }
}
