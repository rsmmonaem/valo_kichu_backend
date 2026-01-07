<?php

namespace App\Services;

use App\Models\PaymentInfo;
use App\Models\PaymentGateway;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PaymentService
{
    public function generateTranId(): string
    {
        return 'TXN' . Carbon::now()->format('YmdHis') . strtoupper(Str::random(6));
    }

    public function createPayment($gatewayName, $amount, $name, $email, $phone, $user = null): array
    {
        if (!$amount) {
            throw new \ValueError("Amount is required");
        }

        $tranId = $this->generateTranId();

        $payment = PaymentInfo::create([
            'user_full_name' => $name,
            'user_email' => $email,
            'user_phone' => $phone,
            'payment_amount' => $amount,
            'payment_gateway' => $gatewayName,
            'transaction_id' => $tranId,
            'status' => PaymentInfo::STATUS_INIT,
            'created_by' => $user?->id,
        ]);

        // Get gateway configuration
        $config = PaymentGateway::where(function($query) use ($gatewayName) {
                $query->where('gateway', $gatewayName)
                      ->orWhere('key', $gatewayName);
            })
            ->where(function($query) {
                $query->where('enable', true)
                      ->orWhere('is_active', true);
            })
            ->first();

        if (!$config) {
            $payment->status = PaymentInfo::STATUS_FAILED;
            $payment->save();
            return [
                'data' => [
                    'status' => 'FAILED',
                    'redirect_url' => null,
                    'payment' => $payment,
                ],
                'status' => 400
            ];
        }

        // TODO: Initialize payment gateway (SSLCommerz, Bkash, Nagad, Stripe)
        // For now, return a placeholder response
        $response = ['status' => 'SUCCESS'];
        $redirectUrl = null;

        if (strtoupper($response['status']) === 'SUCCESS' && $redirectUrl) {
            $payment->status = PaymentInfo::STATUS_PENDING;
            $payment->save();
            return [
                'data' => [
                    'status' => $response['status'],
                    'redirect_url' => $redirectUrl,
                    'payment' => $payment,
                ],
                'status' => 201
            ];
        } else {
            $payment->status = PaymentInfo::STATUS_FAILED;
            $payment->save();
            return [
                'data' => [
                    'status' => $response['status'] ?? 'FAILED',
                    'redirect_url' => $redirectUrl,
                    'payment' => $payment,
                ],
                'status' => 400
            ];
        }
    }
}

