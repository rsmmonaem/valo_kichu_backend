<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->latest();
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate(20));
    }

    public function show(string $id)
    {
        return Order::with(['items.product', 'user.dropshipperProfile', 'items.variation'])->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,purchased_by_admin,ready_to_ship_bd,shipping,delivered,cancelled,refunded',
            'tracking_id' => 'nullable|string',
        ]);

        $order->update($validated);
        if ($validated['status'] === 'delivered') {
            \App\Services\WalletService::distributeCommissions($order);
        }
        // TODO: Trigger Notification based on status change

        return response()->json($order);
    }
}
