<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentInfo extends Model
{
    use HasFactory;

    protected $table = 'payment_info';

    // Payment status constants
    const STATUS_INIT = 'init';
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETE = 'complete';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transaction_uuid',
        'user_full_name',
        'user_phone',
        'user_email',
        'payment_amount',
        'currency',
        'payment_gateway',
        'transaction_id',
        'bank_transaction_id',
        'status',
        'created_by',
        'gateway_response',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'gateway_response' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (!$payment->transaction_uuid) {
                $payment->transaction_uuid = (string) Str::uuid();
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'payment_id');
    }
}
