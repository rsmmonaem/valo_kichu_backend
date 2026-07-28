<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    /**
     * Get Steadfast API Headers
     */
    protected function getHeaders(): array
    {
        $apiKey = BusinessSetting::getValue('steadfast_api_key', config('services.steadfast.api_key', ''));
        $secretKey = BusinessSetting::getValue('steadfast_secret_key', config('services.steadfast.secret_key', ''));

        return [
            'Api-Key' => trim($apiKey),
            'Secret-Key' => trim($secretKey),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get Steadfast Base URL
     */
    protected function getBaseUrl(): string
    {
        $baseUrl = BusinessSetting::getValue('steadfast_base_url', config('services.steadfast.base_url', 'https://portal.packzy.com/api/v1'));
        return rtrim($baseUrl, '/');
    }

    /**
     * Create Order on Steadfast Courier API
     *
     * @param array $data
     * @return array
     */
    public function createOrder(array $data): array
    {
        $headers = $this->getHeaders();

        // 1. Check API Key and Secret Key Configuration
        if (empty($headers['Api-Key']) || empty($headers['Secret-Key'])) {
            return [
                'success' => false,
                'message' => 'Steadfast Courier API Key or Secret Key is missing. Please configure them in Admin Settings -> Global Settings.',
            ];
        }

        // 2. Validate Phone Number (Steadfast requires 11 digits)
        $phone = preg_replace('/[^0-9]/', '', $data['recipient_phone'] ?? '');
        if (strlen($phone) > 11 && str_starts_with($phone, '88')) {
            $phone = substr($phone, 2);
        }
        if (strlen($phone) !== 11) {
            return [
                'success' => false,
                'message' => "Invalid phone number '{$data['recipient_phone']}'. Steadfast Courier requires an 11-digit phone number.",
            ];
        }

        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . '/create_order';

        $payload = array_filter([
            'invoice' => (string) ($data['invoice'] ?? ''),
            'recipient_name' => (string) ($data['recipient_name'] ?? ''),
            'recipient_phone' => $phone,
            'alternative_phone' => isset($data['alternative_phone']) && !empty($data['alternative_phone']) ? (string) $data['alternative_phone'] : null,
            'recipient_email' => isset($data['recipient_email']) && !empty($data['recipient_email']) ? (string) $data['recipient_email'] : null,
            'recipient_address' => (string) ($data['recipient_address'] ?? ''),
            'cod_amount' => (float) ($data['cod_amount'] ?? 0),
            'note' => (string) ($data['note'] ?? ''),
            'item_description' => isset($data['item_description']) ? (string) $data['item_description'] : null,
            'total_lot' => isset($data['total_lot']) ? (int) $data['total_lot'] : null,
            'delivery_type' => isset($data['delivery_type']) ? (int) $data['delivery_type'] : null,
        ], fn($value) => !is_null($value));

        try {
            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->post($url, $payload);

            $responseData = $response->json();

            Log::info("Steadfast API Response for invoice {$payload['invoice']}:", [
                'status' => $response->status(),
                'response' => $responseData,
            ]);

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] == 200) {
                return [
                    'success' => true,
                    'consignment_id' => $responseData['consignment']['consignment_id'] ?? null,
                    'tracking_code' => $responseData['consignment']['tracking_code'] ?? null,
                    'status' => $responseData['consignment']['status'] ?? 'in_review',
                    'message' => $responseData['message'] ?? 'Order sent to Steadfast Courier successfully.',
                    'data' => $responseData,
                ];
            }

            // Extract human-readable error messages if errors object exists
            $errorMessage = $responseData['message'] ?? null;
            if (isset($responseData['errors']) && is_array($responseData['errors'])) {
                $flatErrors = [];
                foreach ($responseData['errors'] as $field => $errs) {
                    if (is_array($errs)) {
                        $flatErrors[] = implode(', ', $errs);
                    } else {
                        $flatErrors[] = (string) $errs;
                    }
                }
                if (!empty($flatErrors)) {
                    $errorMessage = implode(' | ', $flatErrors);
                }
            }

            return [
                'success' => false,
                'message' => $errorMessage ?: 'Failed to create order on Steadfast Courier. Please check API credentials and order details.',
                'data' => $responseData,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error("Steadfast API Connection Timeout: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Could not connect to Steadfast Courier server. Connection timed out.',
            ];
        } catch (\Exception $e) {
            Log::error("Steadfast API Exception: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Steadfast API Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Check delivery status by consignment ID, invoice, or tracking code
     */
    public function getStatus(string $type, string $id): array
    {
        $baseUrl = $this->getBaseUrl();
        $endpoint = match ($type) {
            'cid', 'consignment_id' => "/status_by_cid/{$id}",
            'invoice' => "/status_by_invoice/{$id}",
            'tracking', 'tracking_code' => "/status_by_trackingcode/{$id}",
            default => "/status_by_invoice/{$id}",
        };

        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->get($baseUrl . $endpoint);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("Steadfast getStatus Exception: " . $e->getMessage());
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }

    /**
     * Check current balance
     */
    public function getBalance(): array
    {
        $baseUrl = $this->getBaseUrl();
        try {
            $response = Http::withHeaders($this->getHeaders())
                ->timeout(15)
                ->get($baseUrl . '/get_balance');

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::error("Steadfast getBalance Exception: " . $e->getMessage());
            return ['status' => 500, 'message' => $e->getMessage()];
        }
    }
}
