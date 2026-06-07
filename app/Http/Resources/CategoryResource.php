<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'image' => $this->image,
            'image_url' => $this->image_url, // Added explicit key
            'parent_id' => $this->parent_id,
            'is_active' => (bool) $this->is_active,
            'priority' => (int) $this->priority,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'show_in_bar' => (bool) $this->show_in_bar,
            'show_shop_by_category' => (bool) $this->show_shop_by_category,
            'bar_icon' => $this->bar_icon,
            'custom_icon' => $this->custom_icon,
            'custom_icon_url' => $this->custom_icon_url, // Added explicit key
            'subcategories' => CategoryResource::collection($this->whenLoaded('subcategories')),
        ];
    }
}
