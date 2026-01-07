<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\PaymentInfo;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function initPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'gateway' => 'required|string|in:offline_payment,bkash,nagad,stripe,sslcommerz',
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
