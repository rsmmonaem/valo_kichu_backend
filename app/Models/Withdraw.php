<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Withdraw extends Model
{
    protected $table = 'withdraw';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'payment_method',
        'amount',
        'status',
        'withdrawal_date',
    ];


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            // Transaction ID auto generate
            if (!$model->transaction_id) {
                $model->transaction_id = 'TXN-' . Str::upper(Str::random(10));
            }

            // Withdrawal date auto set (fallback)
            if (!$model->withdrawal_date) {
                $model->withdrawal_date = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
