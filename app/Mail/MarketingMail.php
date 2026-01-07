<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $content;
    public $userName;
    public $template;

    /**
     * Create a new message instance.
     */
    public function __construct(string $subject, string $content, ?string $userName = null, ?string $template = 'marketing')
    {
        $this->subject = $subject;
        $this->content = $content;
        $this->userName = $userName;
        $this->template = $template;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $view = "emails.{$this->template}";
        
        // Fallback to marketing template if custom template doesn't exist
        if (!view()->exists($view)) {
            $view = 'emails.marketing';
        }

        return $this->subject($this->subject)
            ->view($view)
            ->with([
                'content' => $this->content,
                'userName' => $this->userName,
            ]);
    }
}
