<?php

namespace App\Jobs;

use App\Mail\OtpMail;
use App\Helpers\EmailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableTrait;
use Illuminate\Queue\SerializesModels;

class SendOtpEmail implements ShouldQueue
{
    use QueueableTrait, SerializesModels;

    public $email;
    public $otpCode;
    public $userName;

    /**
     * Create a new job instance.
     */
    public function __construct(string $email, string $otpCode, ?string $userName = null)
    {
        $this->email = $email;
        $this->otpCode = $otpCode;
        $this->userName = $userName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Use EmailHelper to send email with dynamic configuration from addon_settings
            EmailHelper::sendEmail($this->email, new OtpMail($this->otpCode, $this->userName), 'otp');
        } catch (\Exception $e) {
            \Log::error("Failed to send OTP email in queue: " . $e->getMessage());
            throw $e;
        }
    }
}
