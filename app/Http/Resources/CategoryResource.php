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
            'image' => $this->image_url, // Use accessor for full URL
            'parent_id' => $this->parent_id,
            'is_active' => (bool) $this->is_active,
            'priority' => (int) $this->priority,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'show_in_bar' => (bool) $this->show_in_bar,
            'bar_icon' => $this->bar_icon,
            'custom_icon' => $this->custom_icon,
            'subcategories' => CategoryResource::collection($this->whenLoaded('subcategories')),
        ];
    }
}
