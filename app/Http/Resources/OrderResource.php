<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Map DB status to Dart Enum String
        $statusMap = [
            'pending' => 'Pending',
            'confirmed' => 'Pending',
            'processing' => 'Packaging', 
            'ready_to_ship_bd' => 'Packaging',
            'shipping' => 'Out of Delivery',
            'delivered' => 'Delivered',
            'purchased_by_admin' => 'Packaging',
            'cancelled' => 'Cancelled',
            'refunded' => 'Cancelled',
        ];

        $paymentStatusMap = [
            'paid' => 'Complete',
            'unpaid' => 'Pending',
            'partial' => 'Pending',
        ];

        return [
            'id' => $this->id,
            'total_price' => (float) $this->total_price,
            'status' => $statusMap[$this->status] ?? 'Pending',
            'payment_status' => $paymentStatusMap[$this->payment_status] ?? 'Pending',
            'transaction_id' => $this->transaction_id,
            'transaction_account_number' => $this->payment?->transaction_id, // Map from payment relation
            'payment_method' => $this->payment_method,
            'user' => $this->whenLoaded('user', function() {
                return new UserResource($this->user);
            }),
            'address' => $this->whenLoaded('address', function() {
                return new AddressResource($this->address);
            }),
            'products' => $this->whenLoaded('items', function() {
                return $this->items->map(function($item) {
                    return [
                        'product' => new ProductResource($item->product),
                        'variant' => $item->product_variation_id, // Dart expects user ID
                        'quantity' => (int) $item->quantity,
                        'price' => (string) $item->unit_price, // Dart expects String
                    ];
                });
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
