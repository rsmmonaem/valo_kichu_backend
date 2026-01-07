<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $invoiceData;
    public $userName;

    /**
     * Create a new message instance.
     */
    public function __construct($order, array $invoiceData = [], ?string $userName = null)
    {
        $this->order = $order;
        $this->invoiceData = $invoiceData;
        $this->userName = $userName;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Invoice - Order #' . ($this->order->id ?? 'N/A'))
            ->view('emails.invoice')
            ->with([
                'order' => $this->order,
                'invoiceData' => $this->invoiceData,
                'userName' => $this->userName,
            ]);
    }
}
