<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $userName;

    /**
     * Create a new message instance.
     */
    public function __construct(string $otpCode, ?string $userName = null)
    {
        $this->otpCode = $otpCode;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Verification Code')
            ->view('emails.otp')
            ->with([
                'otpCode' => $this->otpCode,
                'userName' => $this->userName,
            ]);
    }
}
