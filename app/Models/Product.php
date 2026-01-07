<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\ProductVariation;
use App\Models\User;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'category_id',
        'source_type',
        'supplier_id',
        'created_by_admin_id',
        'base_price',
        'sale_price',
        'stock_quantity',
        'unit',
        'weight_kg',
        'is_active',
        'is_featured',
        'is_deal_of_day',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_deal_of_day' => 'boolean',
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
