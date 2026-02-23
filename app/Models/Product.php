<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\ProductVariation;
use App\Models\User;
use App\Models\BusinessSetting;

class Product extends Model
{
    protected $fillable = [
        'name', 'slug', 'description','short_description', 'category_id', 'brand','category',
        'api_id',
        'product_code',
        'api_from',
        'product_sku', 'product_type', 'unit', 'base_price', 'sale_price',
        'unit_price', 'purchase_price', 'stock_quantity', 'min_order_qty', 'current_stock',
        'discount_type', 'discount_amount', 'tax_amount', 'tax_calculation',
        'shipping_cost', 'shipping_multiply', 'loyalty_point','image','video_link','gallery_images',
        'variations', 'attributes', 'colors', 'tags',
        'status', 'is_featured', 'is_trending', 'is_discounted','specifications',
        'meta_title', 'meta_description', 'meta_keywords', 'meta_image', // SEO Fields
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
        'video_link' => 'string',
        'gallery_images' =>'array',
        'variations' => 'array',
        'attributes' => 'array',
        'colors' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'is_trending' => 'boolean',
        'is_discounted' => 'boolean',
        'specifications' => 'array',
    ];

    protected $appends = [
        'price',
        'discount',
        'discount_type',
        'image_url',
        'gallery_image_urls',
    ];

    public function getPriceAttribute()
    {
        return $this->sale_price ?? $this->base_price;
    }

    /**
     * Calculate price dynamically for a specific user (dropshipper)
     */
    public function getCurrentPriceForUser(User $user)
    {
        $retailPrice = (float) $this->base_price;
        $costPrice = (float) $this->purchase_price;
        
        // Total Profit Pool available to share
        $totalProfit = max(0, $retailPrice - $costPrice);
        
        // Default margins as percentage of the profit pool
        $marginPercent = 0;

        if ($user->role === 'dropshipper') {
            $marginPercent = (float) BusinessSetting::getValue('dropshipper_global_margin', 70);
        } elseif ($user->role === 'sub_dropshipper') {
            $marginPercent = (float) BusinessSetting::getValue('sub_dropshipper_global_margin', 60);
        } elseif ($user->role === 'sub_sub_dropshipper') {
            $marginPercent = (float) BusinessSetting::getValue('sub_sub_dropshipper_global_margin', 50);
        }

        // The dropshipper buys at Retail - (Their % of the profit pool)
        // If they sell at Retail, they earn the marginPercent share of the profit.
        $discount = $totalProfit * ($marginPercent / 100);
        $price = $retailPrice - $discount;

        return round($price, 2);
    }

    public function getDiscountAttribute()
    {
        return 0;
    }

    public function getDiscountTypeAttribute()
    {
        return 'flat';
    }

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        return asset('storage/products/ss' . $this->image);
    }

    public function getGalleryImageUrlsAttribute()
    {
        $gallery = $this->gallery_images;
        if (empty($gallery)) return [];
        
        if (is_string($gallery)) {
            $gallery = json_decode($gallery, true);
        }

        if (!is_array($gallery)) return [];

        return array_map(function($img) {
            if (str_starts_with($img, 'http')) return $img;
            return asset('storage/products/ss' . $img);
        }, $gallery);
    }

    public function getGalleryImagesAttribute($value)
    {
        return $value ? json_decode($value, true) : [];
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
