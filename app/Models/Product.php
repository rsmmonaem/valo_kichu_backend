<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\ProductVariation;
use App\Models\User;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'category_id', 'brand',
        'product_sku', 'product_type', 'unit', 'base_price', 'sale_price',
        'unit_price', 'purchase_price', 'stock_quantity', 'min_order_qty', 'current_stock',
        'discount_type', 'discount_amount', 'tax_amount', 'tax_calculation',
        'shipping_cost', 'shipping_multiply', 'loyalty_point','image','gallery_images',
        'variations', 'attributes', 'colors', 'tags',
        'status', 'is_featured', 'is_trending', 'is_discounted'
    ];


    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'loyalty_point' => 'decimal:2',
        'image' => 'string',
        'gallery_images' =>'array',
        'variations' => 'array',
        'attributes' => 'array',
        'colors' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_discounted' => 'boolean',
    ];

    protected $appends = [
        'price',
        'discount',
        'discount_type'
    ];

    public function getPriceAttribute()
    {
        return $this->sale_price ?? $this->base_price;
    }

    public function getDiscountAttribute()
    {
        return 0;
    }

    public function getDiscountTypeAttribute()
    {
        return 'flat';
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    public function variations()
    {
        return $this->hasMany(ProductVariation::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
