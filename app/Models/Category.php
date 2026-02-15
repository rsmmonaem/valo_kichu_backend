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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
