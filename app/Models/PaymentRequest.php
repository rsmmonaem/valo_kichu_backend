<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use HasUuid;
    use HasFactory;

    protected $table = 'payment_requests';

    protected $fillable = [
        'payer_id',
        'receiver_id',
        'payment_amount',
        'gateway_callback_url',
        'success_hook',
        'failure_hook',
        'transaction_id',
        'currency_code',
        'payment_method',
        'additional_data',
        'is_paid',
        'payer_information',
        'external_redirect_link',
        'receiver_information',
        'attribute_id',
        'attribute',
        'payment_platform',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'additional_data' => 'array',
        'payer_information' => 'array',
        'receiver_information' => 'array',
        'attribute_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the payer (user who is making the payment)
     */
    public function payer()
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    /**
     * Get the receiver (user who is receiving the payment)
     */
    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get the payment gateway
     */
    public function gateway()
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_method', 'key');
    }
}
