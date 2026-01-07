<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    protected $fillable = [
        'min_weight',
        'max_weight',
        'cost',
        'location_zone',
        'is_active',
    ];

    protected $casts = [
        'min_weight' => 'decimal:3',
        'max_weight' => 'decimal:3',
        'cost' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
