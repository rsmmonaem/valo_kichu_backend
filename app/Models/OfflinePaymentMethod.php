<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfflinePaymentMethod extends Model
{
    use HasFactory;

    protected $table = 'offline_payment_methods';

    protected $fillable = [
        'method_name',
        'method_fields',
        'method_informations',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'method_fields' => 'array',
        'method_informations' => 'array',
    ];

    /**
     * Check if the payment method is active
     */
    public function isActive(): bool
    {
        return $this->status == 1;
    }
}
