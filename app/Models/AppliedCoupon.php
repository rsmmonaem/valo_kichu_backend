<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppliedCoupon extends Model
{
    protected $fillable = [
        'user_id',
        'coupon_id',
        'order_id',
        'discount_amount',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
