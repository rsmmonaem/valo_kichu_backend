<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Load images if not already loaded
        if (!$this->relationLoaded('images')) {
            $this->load('images');
        }

        // Get first image for thumbnail
        $images = $this->images instanceof \Illuminate\Support\Collection ? $this->images : collect();
        $firstImage = $images->where('is_primary', true)->first() ?? $images->first();
        $thumbnailUrl = $firstImage ? (str_starts_with($firstImage->path, 'http') ? $firstImage->path : asset('storage/' . $firstImage->path)) : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => (float) ($this->sale_price ?? $this->base_price),
            'base_price' => (float) $this->base_price,
            'sale_price' => (float) $this->sale_price,
            'discount_type' => $this->discount_type ?? 'flat',
            'discount' => (float) ($this->discount ?? 0),
            'stock' => (int) $this->stock_quantity,
            'code' => $this->slug,
            'minimum_order_qty' => (int) ($this->minimum_order_qty ?? 1),
            'brand' => $this->brand_id ? (int) $this->brand_id : null,
            'thumbnail' => $thumbnailUrl,
            'images' => $this->images->map(function($image) {
                return [
                    'id' => $image->id,
                    'image' => str_starts_with($image->path, 'http') ? $image->path : asset('storage/' . $image->path),
                    'is_primary' => (bool) $image->is_primary,
                ];
            })->toArray(),
            'variants' => $this->getVariants(),
            'reviews' => $this->getReviewsSummary(),
        ];
    }

    private function getVariants(): array
    {
        if (!$this->relationLoaded('variations')) {
            $this->load(['variations.images']);
        }
        
        return $this->variations->map(function($variant) {
            return [
                'id' => $variant->id,
                'size' => $variant->size,
                'color' => $variant->color,
                'price' => (float) (($this->sale_price ?? $this->base_price) + $variant->price_modifier),
                'price_modifier' => (float) $variant->price_modifier,
                'stock' => (int) $variant->stock_quantity,
                'sku' => $variant->sku,
                'is_available' => (int) $variant->stock_quantity > 0,
                'images' => ($variant->images instanceof \Illuminate\Support\Collection ? $variant->images : collect())->map(function($image) {
                    return [
                        'id' => $image->id,
                        'image' => str_starts_with($image->path, 'http') ? $image->path : asset('storage/' . $image->path),
                        'is_primary' => (bool) $image->is_primary,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }

    private function getCategoryIds(): array
    {
        $categoryIds = [];
        $category = $this->category;
        while ($category) {
            array_unshift($categoryIds, $category->id);
            $category = $category->parent;
        }
        return $categoryIds;
    }

    private function getReviewsSummary(): array
    {
        // Load reviews if not already loaded
        if (!$this->relationLoaded('reviews')) {
            $this->load('reviews.user');
        }
        
        $reviews = $this->reviews ?? collect();
        $avgRating = $reviews->avg('rating') ?? 0.0;
        $ratingBreakdown = $reviews->groupBy('rating')->map->count();

        $ratingsBreakdown = new \stdClass();
        for ($i = 1; $i <= 5; $i++) {
            $ratingsBreakdown->{(string)$i} = (int) ($ratingBreakdown[$i] ?? 0);
        }

        return [
            'rating_summary' => [
                'average_ratings' => (float) round($avgRating, 2),
                'total_reviews' => $reviews->count(),
                'ratings_breakdown' => $ratingsBreakdown,
            ],
            'reviews' => $reviews->map(function($review) {
                return [
                    'id' => $review->id,
                    'comment' => $review->comment,
                    'rating' => $review->rating,
                    'created_at' => $review->created_at?->toDateTimeString(),
                    'customer' => $review->user ? [
                        'id' => $review->user->id,
                        'first_name' => $review->user->first_name,
                        'last_name' => $review->user->last_name,
                        'email' => $review->user->email,
                        'phone_number' => $review->user->phone_number,
                        'image' => $review->user->image_url, // Use accessor for full URL
                    ] : null,
                    'product' => $this->id,
                ];
            })->toArray(),
        ];
    }
}
