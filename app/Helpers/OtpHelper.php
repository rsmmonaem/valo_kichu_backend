<?php

namespace App\Helpers;

use App\Models\AddonSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OtpHelper
{
    /**
     * Send OTP via SMS using addon_settings
     *
     * @param string $phoneNumber
     * @param string $otpCode
     * @return bool
     */
    public static function sendSmsOtp(string $phoneNumber, string $otpCode): bool
    {
        try {
            $smsGateway = AddonSetting::getActiveSmsGateway();
            
            if (!$smsGateway) {
                Log::warning('No active SMS gateway found');
                return false;
            }

            $values = $smsGateway->getValues();
            $gateway = $values['gateway'] ?? null;
            $status = $values['status'] ?? '0';

            // Check if gateway is active
            if ($status == '0' || $status === 0 || $status === false) {
                Log::info("SMS Gateway {$gateway} is not active");
                return false;
            }

            // Send SMS based on gateway type
            switch ($gateway) {
                case 'twilio':
                    return self::sendViaTwilio($phoneNumber, $otpCode, $values);
                
                case 'nexmo':
                case 'vonage':
                    return self::sendViaNexmo($phoneNumber, $otpCode, $values);
                
                case 'msg91':
                    return self::sendViaMsg91($phoneNumber, $otpCode, $values);
                
                case '2factor':
                    return self::sendVia2Factor($phoneNumber, $otpCode, $values);
                
                case 'releans':
                    return self::sendViaReleans($phoneNumber, $otpCode, $values);
                
                case 'signal_wire':
                    return self::sendViaSignalWire($phoneNumber, $otpCode, $values);
                
                case 'hubtel':
                    return self::sendViaHubtel($phoneNumber, $otpCode, $values);
                
                case 'viatech':
                    return self::sendViaViatech($phoneNumber, $otpCode, $values);
                
                case '019_sms':
                    return self::sendVia019Sms($phoneNumber, $otpCode, $values);
                
                case 'global_sms':
                    return self::sendViaGlobalSms($phoneNumber, $otpCode, $values);
                
                case 'akandit_sms':
                    return self::sendViaAkanditSms($phoneNumber, $otpCode, $values);
                
                case 'alphanet_sms':
                    return self::sendViaAlphanetSms($phoneNumber, $otpCode, $values);
                
                case 'sms_to':
                    return self::sendViaSmsTo($phoneNumber, $otpCode, $values);
                
                case 'paradox':
                    return self::sendViaParadox($phoneNumber, $otpCode, $values);
                
                case 'bulksmsbd':
                    return self::sendViaBulkSmsBd($phoneNumber, $otpCode, $values);
                
                case 'mram_sms':
                    return self::sendViaMramSms($phoneNumber, $otpCode, $values);
                
                default:
                    Log::warning("Unsupported SMS gateway: {$gateway}");
                    return false;
            }
        } catch (\Exception $e) {
            Log::error("Failed to send SMS OTP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Twilio
     */
    private static function sendViaTwilio(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $sid = $values['sid'] ?? null;
            $token = $values['token'] ?? null;
            $from = $values['from'] ?? $values['messaging_service_sid'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$sid || !$token || !$from) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $phoneNumber,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Twilio SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Nexmo/Vonage
     */
    private static function sendViaNexmo(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $apiSecret = $values['api_secret'] ?? null;
            $from = $values['from'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiKey || !$apiSecret || !$from) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::post('https://rest.nexmo.com/sms/json', [
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
                'from' => $from,
                'to' => $phoneNumber,
                'text' => $message,
            ]);

            return $response->successful() && ($response->json()['messages'][0]['status'] ?? '') == '0';
        } catch (\Exception $e) {
            Log::error("Nexmo SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via MSG91
     */
    private static function sendViaMsg91(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $authKey = $values['auth_key'] ?? null;
            $templateId = $values['template_id'] ?? null;
            
            if (!$authKey || !$templateId) {
                return false;
            }

            $response = Http::post('https://api.msg91.com/api/v5/otp', [
                'authkey' => $authKey,
                'mobile' => $phoneNumber,
                'template_id' => $templateId,
                'otp' => $otpCode,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("MSG91 SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via 2Factor
     */
    private static function sendVia2Factor(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            
            if (!$apiKey) {
                return false;
            }

            $response = Http::get("https://2factor.in/API/V1/{$apiKey}/SMS/{$phoneNumber}/{$otpCode}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("2Factor SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Releans
     */
    private static function sendViaReleans(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $from = $values['from'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiKey || !$from) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post('https://api.releans.com/v2/sender/message', [
                'sender' => $from,
                'mobile' => $phoneNumber,
                'content' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Releans SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Signal Wire
     */
    private static function sendViaSignalWire(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $projectId = $values['project_id'] ?? null;
            $token = $values['token'] ?? null;
            $spaceUrl = $values['space_url'] ?? null;
            $from = $values['from'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$projectId || !$token || !$spaceUrl || !$from) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withBasicAuth($projectId, $token)
                ->post("https://{$spaceUrl}/api/relay/rest/messages", [
                    'From' => $from,
                    'To' => $phoneNumber,
                    'Body' => $message,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Signal Wire SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Hubtel
     */
    private static function sendViaHubtel(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $clientId = $values['client_id'] ?? null;
            $clientSecret = $values['client_secret'] ?? null;
            $senderId = $values['sender_id'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$clientId || !$clientSecret || !$senderId) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withBasicAuth($clientId, $clientSecret)
                ->post('https://devapi.hubtel.com/v1/messages/send', [
                    'From' => $senderId,
                    'To' => $phoneNumber,
                    'Content' => $message,
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Hubtel SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Viatech
     */
    private static function sendViaViatech(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiUrl = $values['api_url'] ?? null;
            $apiKey = $values['api_key'] ?? null;
            $senderId = $values['sender_id'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiUrl || !$apiKey || !$senderId) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post($apiUrl, [
                'sender_id' => $senderId,
                'mobile' => $phoneNumber,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Viatech SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via 019 SMS
     */
    private static function sendVia019Sms(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $username = $values['username'] ?? null;
            $password = $values['password'] ?? null;
            $sender = $values['sender'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$username || !$password || !$sender) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            // 019 SMS API implementation
            $response = Http::post('https://api.019sms.com/api/v1/send', [
                'username' => $username,
                'password' => $password,
                'sender' => $sender,
                'mobile' => $phoneNumber,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("019 SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Global SMS
     */
    private static function sendViaGlobalSms(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $userName = $values['user_name'] ?? null;
            $password = $values['password'] ?? null;
            $from = $values['from'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$userName || !$password || !$from) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::post('https://api.globalsms.com/v1/send', [
                'username' => $userName,
                'password' => $password,
                'from' => $from,
                'to' => $phoneNumber,
                'text' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Global SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Akandit SMS
     */
    private static function sendViaAkanditSms(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $username = $values['username'] ?? null;
            $password = $values['password'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$username || !$password) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::post('https://api.akanditsms.com/v1/send', [
                'username' => $username,
                'password' => $password,
                'mobile' => $phoneNumber,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Akandit SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Alphanet SMS
     */
    private static function sendViaAlphanetSms(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiKey) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post('https://api.alphanetsms.com/v1/send', [
                'mobile' => $phoneNumber,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Alphanet SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via SMS To
     */
    private static function sendViaSmsTo(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $senderId = $values['sender_id'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiKey || !$senderId) {
                return false;
            }

            $message = str_replace('{code}', $otpCode, $template);
            
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post('https://api.smsto.com/v1/send', [
                'sender_id' => $senderId,
                'to' => $phoneNumber,
                'message' => $message,
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("SMS To error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via Paradox
     */
    private static function sendViaParadox(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $senderId = $values['sender_id'] ?? null;
            
            if (!$apiKey || !$senderId) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post('https://api.paradox.com/v1/send', [
                'sender_id' => $senderId,
                'to' => $phoneNumber,
                'message' => "Your OTP code is: {$otpCode}",
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("Paradox SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via BulkSMSBD
     */
    private static function sendViaBulkSmsBd(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $senderId = $values['senderid'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiKey || !$senderId) {
                Log::warning("BulkSMSBD: Missing api_key or senderid");
                return false;
            }

            // Replace {code} placeholder in template
            $message = str_replace('{code}', $otpCode, $template);
            
            // BulkSMSBD API endpoint
            $apiUrl = 'http://bulksmsbd.net/api/smsapi';
            
            // Prepare payload
            $payload = [
                'api_key' => $apiKey,
                'type' => 'text',
                'number' => $phoneNumber,
                'senderid' => $senderId,
                'message' => $message,
            ];
            
            // Make POST request
            $response = Http::asForm()->post($apiUrl, $payload);
            
            // Check if request was successful
            if ($response->successful()) {
                Log::info("BulkSMSBD SMS sent successfully to {$phoneNumber}");
                return true;
            } else {
                Log::warning("BulkSMSBD API error: " . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("BulkSMSBD SMS error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send via MRAM SMS
     * API Documentation: https://sms.mram.com.bd
     */
    private static function sendViaMramSms(string $phoneNumber, string $otpCode, array $values): bool
    {
        try {
            $apiKey = $values['api_key'] ?? null;
            $senderId = $values['senderid'] ?? null;
            $template = $values['otp_template'] ?? 'Your OTP code is: {code}';
            
            if (!$apiKey || !$senderId) {
                Log::warning("MRAM SMS: Missing api_key or senderid");
                return false;
            }


            // Replace {code} placeholder in template
            $message = str_replace('{code}', $otpCode, $template);
            
            // Format phone number (ensure it starts with country code, e.g., 88017XXXXXXXX)
            // Remove any + sign and ensure proper format
            $contacts = preg_replace('/[^0-9]/', '', $phoneNumber);
            if (!str_starts_with($contacts, '880')) {
                // If it doesn't start with 880, add it
                if (str_starts_with($contacts, '0')) {
                    $contacts = '880' . substr($contacts, 1);
                } else {
                    $contacts = '880' . $contacts;
                }
            }
            
            // URL encode the message to handle special characters
            // $encodedMessage = urlencode($message);
            
            // MRAM SMS API endpoint (GET & POST supported)
            $apiUrl = 'https://sms.mram.com.bd/smsapi';
            
            // Prepare query parameters
            $params = [
                'api_key' => $apiKey,
                'type' => 'text', // text for normal SMS, unicode for Bangla SMS
                'contacts' => $contacts,
                'senderid' => $senderId,
                'msg' => $message,
            ];
            
            // Make GET request (API supports both GET and POST)
            $response = Http::get($apiUrl, $params);

            $responseBody = $response->body();
        
            $errorCodes = [
                "1002","1003","1004","1005","1006","1007",
                "1008","1009","1010","1011","1012","1013",
                "1014","1015","1016","1019"
            ];

            if (in_array($responseBody, $errorCodes)) {
                Log::warning("MRAM SMS API error: " . $responseBody);
                return false;
            }

            // If not an error code → success
            Log::info("MRAM SMS sent successfully to {$phoneNumber}. Response: {$responseBody}");
            return true;
        } catch (\Exception $e) {
            Log::error("MRAM SMS error: " . $e->getMessage());
            return false;
        }
    }
}

