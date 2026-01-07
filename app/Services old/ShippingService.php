<?php

namespace App\Services;

use App\Models\ShippingRate;

class ShippingService
{
    public function calculateCost(float $weight, string $locationZone = 'default'): float
    {
        // Find rate for weight range
        $rate = ShippingRate::where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight)
            ->where('location_zone', $locationZone)
            ->where('is_active', true)
            ->first();

        if ($rate) {
            return $rate->cost;
        }

        // Fallback logic or default base rate
        return 100.00; 
    }
}
