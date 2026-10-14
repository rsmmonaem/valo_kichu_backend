<?php

namespace App\Services;

use App\Models\Order;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapiService
{
    /**
     * Send a verified "Purchase" event when an order is officially DELIVERED (COD Order Protection)
     */
    public static function sendDeliveredPurchase(Order $order): array
    {
        return self::sendOrderEvent('Purchase', $order, 'delivered_order_' . ($order->order_number ?: $order->id), [
            'delivery_status' => 'delivered',
            'delivery_category' => 'home_delivery',
        ]);
    }

    /**
     * Send a "Refund" event to Meta when an order is cancelled or refunded
     */
    public static function sendRefund(Order $order, ?float $refundAmount = null): array
    {
        $amount = $refundAmount !== null ? $refundAmount : (float) ($order->total_price ?? 0);
        return self::sendOrderEvent('Refund', $order, 'refund_order_' . ($order->order_number ?: $order->id), [
            'value' => (float) number_format($amount, 2, '.', ''),
            'reason' => 'cancelled_or_refunded',
        ]);
    }

    /**
     * Core method to dispatch any Order-related Conversions API (CAPI) event
     */
    public static function sendOrderEvent(string $eventName, Order $order, ?string $eventId = null, array $extraCustomData = []): array
    {
        try {
            // Check if CAPI is enabled in database settings
            $isCapiEnabled = BusinessSetting::getValue('facebook_capi_enabled', null);
            if ($isCapiEnabled === null) {
                $isCapiEnabled = env('FACEBOOK_CAPI_ENABLED', 'true');
            }
            $isEnabled = ($isCapiEnabled === 'true' || $isCapiEnabled === '1' || $isCapiEnabled === true);

            if (!$isEnabled) {
                Log::info("Meta CAPI: Skipped {$eventName} for Order #{$order->order_number}. CAPI is currently disabled in settings.");
                return ['success' => false, 'message' => 'Meta CAPI is disabled in settings.'];
            }

            // Retrieve credentials with auto-correct for legacy typos
            $pixelId = BusinessSetting::getValue('facebook_pixel_id', env('FACEBOOK_PIXEL_ID', env('NEXT_PUBLIC_FACEBOOK_PIXEL_ID', '996111553309299')));
            if ($pixelId === '99611553309299' || empty($pixelId)) {
                $pixelId = env('FACEBOOK_PIXEL_ID', env('NEXT_PUBLIC_FACEBOOK_PIXEL_ID', '996111553309299'));
            }

            $accessToken = BusinessSetting::getValue('facebook_access_token', env('FACEBOOK_ACCESS_TOKEN', ''));
            if (empty($accessToken)) {
                $accessToken = env('FACEBOOK_ACCESS_TOKEN', '');
            }

            if (empty($pixelId) || empty($accessToken)) {
                Log::warning("Meta CAPI: Missing Pixel ID or Access Token for order {$order->order_number}");
                return ['success' => false, 'message' => 'Missing Pixel ID or Access Token'];
            }

            // Prepare User Data with SHA-256 Hashing according to Meta Specifications
            $userData = self::buildHashedUserData($order);

            // Prepare Items Contents
            $order->loadMissing('items.product');
            $contents = [];
            if ($order->items && $order->items->count() > 0) {
                foreach ($order->items as $item) {
                    $contents[] = [
                        'id' => (string) ($item->product_id ?? $item->id),
                        'quantity' => (int) ($item->quantity ?? 1),
                        'item_price' => (float) number_format((float) ($item->unit_price ?? 0), 2, '.', ''),
                    ];
                }
            }

            $eventTime = time();
            $deterministicEventId = $eventId ?: "capi_{$eventName}_" . ($order->order_number ?: $order->id) . "_{$eventTime}";

            $customData = array_merge([
                'currency' => strtoupper($order->currency ?: 'BDT'),
                'value' => (float) number_format((float) ($order->total_price ?? 0), 2, '.', ''),
                'order_id' => (string) ($order->order_number ?: $order->id),
                'content_type' => 'product',
                'contents' => $contents,
                'num_items' => count($contents),
            ], $extraCustomData);

            $payload = [
                'data' => [
                    [
                        'event_name' => $eventName,
                        'event_time' => $eventTime,
                        'action_source' => 'website',
                        'event_id' => $deterministicEventId,
                        'user_data' => $userData,
                        'custom_data' => $customData,
                    ],
                ],
            ];

            // Optional Test Event Code
            $testEventCode = BusinessSetting::getValue('facebook_test_event_code', env('FACEBOOK_TEST_EVENT_CODE', null));
            if (!empty($testEventCode)) {
                $payload['test_event_code'] = $testEventCode;
            }

            // Execute HTTP Request to Meta Graph API v19.0
            $url = "https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$accessToken}";
            $response = Http::timeout(5)->post($url, $payload);

            if ($response->successful()) {
                Log::info("Meta CAPI Success: {$eventName} sent for Order #{$order->order_number} [EventID: {$deterministicEventId}]");
                return [
                    'success' => true,
                    'event_id' => $deterministicEventId,
                    'response' => $response->json(),
                ];
            } else {
                Log::error("Meta CAPI Error for Order #{$order->order_number}: " . $response->body());
                return [
                    'success' => false,
                    'error' => $response->json(),
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Meta CAPI Exception for Order #{$order->order_number}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Build standard hashed user_data according to Meta Specifications
     */
    protected static function buildHashedUserData(Order $order): array
    {
        $userData = [];

        // 1. Email
        $rawEmail = $order->email ?: ($order->user?->email ?? null);
        if ($rawEmail) {
            $userData['em'] = [hash('sha256', strtolower(trim($rawEmail)))];
        }

        // 2. Phone Number (normalized for Bangladesh +880)
        $rawPhone = $order->contact_number ?: ($order->phone ?: ($order->user?->phone_number ?? null));
        if ($rawPhone) {
            $digits = preg_replace('/\D/', '', (string) $rawPhone);
            if (str_starts_with($digits, '0') && strlen($digits) === 11) {
                $digits = '88' . $digits; // 017... -> 88017...
            } elseif (str_starts_with($digits, '1') && strlen($digits) === 10) {
                $digits = '880' . $digits; // 17... -> 88017...
            }
            $userData['ph'] = [hash('sha256', $digits)];
        }

        // 3. Name (Split First & Last)
        $rawName = $order->name ?: ($order->user?->name ?? null);
        if ($rawName) {
            $parts = preg_split('/\s+/', trim($rawName));
            if (!empty($parts[0])) {
                $userData['fn'] = [hash('sha256', strtolower($parts[0]))];
            }
            if (count($parts) > 1) {
                $userData['ln'] = [hash('sha256', strtolower(implode(' ', array_slice($parts, 1))))];
            }
        }

        // 4. City from Address
        $city = null;
        if (!empty($order->shipping_address)) {
            try {
                $addr = is_array($order->shipping_address) ? $order->shipping_address : json_decode($order->shipping_address, true);
                if (is_array($addr)) {
                    $city = $addr['city'] ?? ($addr['area'] ?? null);
                }
            } catch (\Exception $e) {
                // Ignore parse error
            }
        }
        if (!empty($city)) {
            $userData['ct'] = [hash('sha256', strtolower(trim($city)))];
        }

        // 5. Country: Bangladesh (bd)
        $userData['country'] = [hash('sha256', 'bd')];

        // 6. External ID
        $externalId = $order->user_id ?: ($order->order_number ?: (string) $order->id);
        if ($externalId) {
            $userData['external_id'] = [hash('sha256', (string) $externalId)];
        }

        return $userData;
    }
}
