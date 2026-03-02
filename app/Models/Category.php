<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'image',
        'is_active',
        'priority',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'show_in_bar',  
        'bar_icon', 
        'custom_icon',
        'show_shop_by_category',
    ];

    protected $appends = [
        'image_url',
        'custom_icon_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        $path = str_starts_with($this->image, 'products/') ? $this->image : 'products/' . $this->image;
        return asset('storage/' . $path);
    }

    public function getCustomIconUrlAttribute()
    {
        if (!$this->custom_icon) return null;
        if (str_starts_with($this->custom_icon, 'http')) return $this->custom_icon;
        return asset('storage/' . $this->custom_icon);
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subcategories()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function images()
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public static function getAllChildCategoryIds($categoryId)
    {
        $categoryIds = [$categoryId];
        $children = self::where('parent_id', $categoryId)->pluck('id')->toArray();
        
        foreach ($children as $childId) {
            $categoryIds = array_merge($categoryIds, self::getAllChildCategoryIds($childId));
        }
        
        return array_unique($categoryIds);
    }

}
