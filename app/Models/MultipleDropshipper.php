<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MultipleDropshipper extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'dropshipper_config',
        'product_store_config',
        'order_store_config',
        'status_check_config'
    ];

    protected $casts = [
        'dropshipper_config' => 'array',
        'product_store_config' => 'array',
        'order_store_config' => 'array',
        'status_check_config' => 'array',
        'is_active' => 'boolean',
    ];
     public function products()
    {
        return $this->hasMany(Product::class);
    }
    
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
