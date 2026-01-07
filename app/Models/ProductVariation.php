<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

class ProductVariation extends Model
{
    protected $fillable = [
        'product_id',
        'size',
        'color',
        'price_modifier',
        'stock_quantity',
        'sku',
        'images',
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
        'images' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
