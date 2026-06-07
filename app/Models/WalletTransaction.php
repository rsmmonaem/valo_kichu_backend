<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'description',
        'reference_type',
        'reference_id',
        'meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
