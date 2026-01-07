<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'name',
        'phone',
        'email',
        'address_line1',
        'address_line2',
        'city',
        'district',
        'state',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'is_billing',
        'is_shipping',
    ];
}
