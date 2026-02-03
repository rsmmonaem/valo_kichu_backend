<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;

class ShippingMethodController extends Controller
{
    public function index()
    {
        return response()->json(ShippingMethod::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'cost' => 'required|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $method = ShippingMethod::create($validated);
        return response()->json($method, 201);
    }

    public function update(Request $request, ShippingMethod $shippingMethod)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'cost' => 'sometimes|numeric|min:0',
            'is_active' => 'boolean'
        ]);

        $shippingMethod->update($validated);
        return response()->json($shippingMethod);
    }

    public function destroy(ShippingMethod $shippingMethod)
    {
        $shippingMethod->delete();
        return response()->json(null, 204);
    }
}
