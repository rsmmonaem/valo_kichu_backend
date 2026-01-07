<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $langCode = $request->header('X-Language') ?? $request->header('Accept-Language');
        
        // Get first image for thumbnail
        $firstImage = $this->whenLoaded('images', function() {
            return $this->images->first();
        }, null);
        
        return [
            'vendor' => $this->whenLoaded('vendor', $this->vendor),
            'id' => $this->id,
            'name' => $this->getName($langCode),
            'description' => $this->getDescription($langCode),
            'price' => (float) $this->price,
            'discount_type' => $this->discount_type,
            'discount' => (float) $this->discount,
            'stock' => (int) $this->stock,
            'code' => $this->product_code,
            'minimum_order_qty' => (int) $this->minimum_order_qty,
            'brand' => $this->brand_id ? (int) $this->brand_id : null,
            'thumbnail' => $firstImage ? $firstImage->image_url : null, // Use accessor for full URL
            'images' => $this->getProductImages(),
            'variants' => $this->getVariants(),
            'reviews' => $this->getReviewsSummary(),
        ];
    }

    private function getProductImages(): array
    {
        if (!$this->relationLoaded('images')) {
            $this->load('images');
        }
        
        return $this->images->map(function($image) {
            return [
                'id' => $image->id,
                'image' => $image->image_url, // Use accessor for full URL
            ];
        })->toArray();
    }

    private function getVariants(): array
    {
        if (!$this->relationLoaded('variants')) {
            $this->load(['variants.images', 'variants.attributes']);
        }
        
        return $this->variants->map(function($variant) {
            return [
                'id' => $variant->id,
                'variant_name' => $variant->variant_name,
                'attributes' => $variant->attributes->pluck('attribute_type')->toArray(),
                'price' => (float) $variant->price,
                'discount_type' => $variant->discount_type,
                'discount' => (float) $variant->discount,
                'stock' => (int) $variant->stock,
                'is_available' => (bool) $variant->is_available,
                'images' => $variant->images->map(function($image) {
                    return [
                        'id' => $image->id,
                        'image' => $image->image_url, // Use accessor for full URL
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
