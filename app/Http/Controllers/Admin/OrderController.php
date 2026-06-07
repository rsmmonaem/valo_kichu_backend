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

        if ($request->has('order_type')) {
            if ($request->order_type === 'customer') {
                $query->whereIn('order_type', ['direct', 'referral']);
            } elseif ($request->order_type === 'dropshipper' || $request->order_type === 'dropshipping') {
                $query->where('order_type', 'dropshipping');
            } else {
                $query->where('order_type', $request->order_type);
            }
        }

        $perPage = $request->get('per_page', 20);
        return response()->json($query->paginate($perPage));
    }

    public function show(string $id)
    {
        return Order::with(['items.product', 'user', 'items.variation'])->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,purchased_by_admin,ready_to_ship_bd,shipping,delivered,cancelled,refunded',
            'tracking_id' => 'nullable|string',
        ]);

        $order->update($validated);

        // TODO: Trigger Notification based on status change

        return response()->json($order);
    }
}
