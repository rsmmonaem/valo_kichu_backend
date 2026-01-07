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
    ];

    protected $casts = [
        'price_modifier' => 'decimal:2',
    ];

    protected $appends = [
        'price',
        'discount',
        'discount_type'
    ];

    public function getPriceAttribute()
    {
        $basePrice = $this->product->sale_price ?? $this->product->base_price;
        return (float) ($basePrice + $this->price_modifier);
    }

    public function getDiscountAttribute()
    {
        return $this->product->discount;
    }

    public function getDiscountTypeAttribute()
    {
        return $this->product->discount_type;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
