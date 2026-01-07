<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use App\Helpers\OtpHelper;
use App\Jobs\SendOtpEmail;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Generate a 6-digit OTP code
     *
     * @return string
     */
    public function generateOtp(): string
    {
        // For development/testing, you can return a fixed OTP
        if (env('APP_DEBUG', true)) {
            return '123456';
        } else {
            return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        }
    }

    /**
     * Create or update OTP for a user
     *
     * @param User $user
     * @param string|null $code
     * @return Otp
     */
    public function createOrUpdateOtp(User $user, ?string $code = null): Otp
    {
        $code = $code ?? $this->generateOtp();

        return Otp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => $code,
                'created_at' => now(),
            ]
        );
    }

    /**
     * Verify OTP for a user
     *
     * @param User $user
     * @param string $otpCode
     * @return array ['success' => bool, 'message' => string, 'otp' => Otp|null]
     */
    public function verifyOtp(User $user, string $otpCode): array
    {
        $otp = Otp::where('user_id', $user->id)
            ->where('code', $otpCode)
            ->first();

        if (!$otp) {
            return [
                'success' => false,
                'message' => 'Invalid OTP.',
                'otp' => null,
            ];
        }

        if ($otp->isExpired()) {
            $otp->delete();
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
                'otp' => null,
            ];
        }

        return [
            'success' => true,
            'message' => 'OTP verified successfully.',
            'otp' => $otp,
        ];
    }

    /**
     * Delete OTP for a user
     *
     * @param User $user
     * @return bool
     */
    public function deleteOtp(User $user): bool
    {
        return Otp::where('user_id', $user->id)->delete();
    }

    /**
     * Send OTP via email (queued in background)
     *
     * @param User $user
     * @param string $otpCode
     * @param string|null $email
     * @return bool
     */
    public function sendOtpViaEmail(User $user, string $otpCode, ?string $email = null): bool
    {
        $emailAddress = $email ?? $user->email;
        
        if (!$emailAddress) {
            return false;
        }

        try {
            // Queue email in background
            $userName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
            SendOtpEmail::dispatch($emailAddress, $otpCode, $userName ?: null);
            
            \Log::info("OTP email queued for {$emailAddress}");
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to queue OTP email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP via SMS using addon_settings
     *
     * @param User $user
     * @param string $otpCode
     * @param string|null $phoneNumber
     * @return bool
     */
    public function sendOtpViaSms(User $user, string $otpCode, ?string $phoneNumber = null): bool
    {
        $phone = $phoneNumber ?? $user->phone_number;
        
        if (!$phone) {
            return false;
        }

        try {
            // Use OtpHelper to send SMS via addon_settings
            return OtpHelper::sendSmsOtp($phone, $otpCode);
        } catch (\Exception $e) {
            \Log::error("Failed to send OTP SMS: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP to user (email or SMS based on what's available)
     * Creates/updates OTP in database and sends it
     *
     * @param User $user
     * @param string|null $phoneNumber
     * @param string|null $email
     * @return array ['success' => bool, 'method' => string, 'message' => string, 'otp' => Otp|null]
     */
    public function sendOtp(User $user, ?string $phoneNumber = null, ?string $email = null): array
    {
        // Generate and create/update OTP in database
        $otp = $this->createOrUpdateOtp($user);
        $otpCode = $otp->code;

        // Try email first if provided
        if ($email && $email !== '') {
            $sent = $this->sendOtpViaEmail($user, $otpCode, $email);
            if ($sent) {
                return [
                    'success' => true,
                    'method' => 'email',
                    'message' => 'OTP sent to email successfully.',
                    'otp' => $otp,
                ];
            }
        }

        // Fallback to phone if email failed or not provided
        if ($phoneNumber && $phoneNumber !== '') {
            $sent = $this->sendOtpViaSms($user, $otpCode, $phoneNumber);
            if ($sent) {
                return [
                    'success' => true,
                    'method' => 'sms',
                    'message' => 'OTP sent to phone number successfully.',
                    'otp' => $otp,
                ];
            }
        }

        return [
            'success' => false,
            'method' => null,
            'message' => 'Failed to send OTP. Please check your email or phone number.',
            'otp' => null,
        ];
    }
}

