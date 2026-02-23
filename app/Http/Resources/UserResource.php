<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'image' => $this->image_url, // Use accessor for full URL
            'address' => $this->address,
            'fcm_token' => $this->fcm_token,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'is_staff' => $this->is_staff,
            'is_verified' => $this->is_verified ?? false,
            'phone_number_verified_at' => $this->phone_number_verified_at?->toDateTimeString(),
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'refer_code' => $this->refer_code,
            'refer_by' => $this->refer_by,
            'is_any_dropshipper' => $this->isAnyDropshipper(),
            'store_name' => $this->store_name, // Using the accessor I just added
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
