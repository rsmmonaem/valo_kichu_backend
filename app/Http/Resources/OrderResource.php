<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'transaction_id' => $this->transaction_id,
            'transaction_account_number' => $this->transaction_account_number,
            'payment_method' => $this->payment_method,
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'first_name' => $this->user->first_name,
                    'last_name' => $this->user->last_name,
                    'image' => $this->user->image ? asset('storage/' . $this->user->image) : null,
                    'phone_number' => $this->user->phone_number,
                    'email' => $this->user->email,
                ];
            }),
            'address' => $this->whenLoaded('address', function() {
                return [
                    'id' => $this->address->id,
                    'title' => $this->address->title,
                    'name' => $this->address->name,
                    'phone' => $this->address->phone,
                    'email' => $this->address->email,
                    'address_line1' => $this->address->address_line1,
                    'address_line2' => $this->address->address_line2,
                    'city' => $this->address->city,
                    'district' => $this->address->district,
                    'state' => $this->address->state,
                    'postal_code' => $this->address->postal_code,
                    'country' => $this->address->country,
                ];
            }),
            'products' => $this->whenLoaded('orderItems', function() {
                return $this->orderItems->map(function($item) {
                    return [
                        'product' => [
                            'id' => $item->product->id,
                            'name' => $item->product->getName(),
                            'price' => $item->product->price,
                            'discount_price' => $item->product->discount_price,
                            'images' => $item->product->images->map(function($img) {
                                return ['image' => asset('storage/' . $img->image)];
                            }),
                        ],
                        'variant' => $item->variant ? [
                            'id' => $item->variant->id,
                            'variant_name' => $item->variant->variant_name,
                            'price' => $item->variant->price,
                            'discount_price' => $item->variant->discount_price,
                        ] : null,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ];
                });
            }),
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
