<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'business_category' => $this->business_category,
            'image' => $this->image_url, // Use accessor for full URL
            'banner' => $this->banner_url, // Use accessor for full URL
            'business_verifier_name' => $this->business_verifier_name,
            'division_id' => $this->division_id,
            'district_id' => $this->district_id,
            'city_id' => $this->city_id,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'phone_number' => $this->phone_number,
            'status' => $this->status,
            'division' => $this->whenLoaded('division'),
            'district' => $this->whenLoaded('district'),
            'city' => $this->whenLoaded('city'),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

