<?php

namespace App\Jobs;

use App\Helpers\EmailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable as QueueableTrait;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendEmail implements ShouldQueue
{
    use QueueableTrait, SerializesModels;

    public $email;
    public $mailable;
    public $emailType;

    /**
     * Create a new job instance.
     * 
     * @param string $email Recipient email address
     * @param Mailable $mailable The mailable instance to send
     * @param string|null $emailType Type of email (otp, invoice, marketing, etc.)
     */
    public function __construct(string $email, Mailable $mailable, ?string $emailType = null)
    {
        $this->email = $email;
        $this->mailable = $mailable;
        $this->emailType = $emailType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Use EmailHelper to send email with dynamic configuration from addon_settings
            EmailHelper::sendEmail($this->email, $this->mailable, $this->emailType);
        } catch (\Exception $e) {
            \Log::error("Failed to send email in queue: " . $e->getMessage());
            throw $e;
        }
    }
}
