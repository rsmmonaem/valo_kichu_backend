<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariation;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variation_id',
        'quantity',
        'unit_price',
        'purchase_price',
        'total_price',
        'product_name',
        'variation_snapshot',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(ProductVariation::class, 'product_variation_id');
    }
}
