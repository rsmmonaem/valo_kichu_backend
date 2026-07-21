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

        if ($request->has('status') && $request->status !== 'all') {
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

        if ($request->has('start_date') && !empty($request->start_date)) {
            $start = \Carbon\Carbon::parse($request->start_date, 'Asia/Dhaka')->startOfDay()->setTimezone('UTC');
            $query->where('created_at', '>=', $start);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $end = \Carbon\Carbon::parse($request->end_date, 'Asia/Dhaka')->endOfDay()->setTimezone('UTC');
            $query->where('created_at', '<=', $end);
        }

        if ($request->has('category_id') && !empty($request->category_id)) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('id', 'like', "%{$searchTerm}%")
                  ->orWhere('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_number', 'like', "%{$searchTerm}%")
                  ->orWhereHas('user', function($uq) use ($searchTerm) {
                      $uq->where('name', 'like', "%{$searchTerm}%")
                         ->orWhere('first_name', 'like', "%{$searchTerm}%")
                         ->orWhere('last_name', 'like', "%{$searchTerm}%")
                         ->orWhere('email', 'like', "%{$searchTerm}%")
                         ->orWhere('phone_number', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Calculate summary stats based on the unpaginated filtered query
        $orderIds = $query->pluck('id');
        $totalOrdersCount = $orderIds->count();
        
        // Only sum total sales for valid order statuses
        $validSalesQuery = clone $query;
        $validSalesQuery->whereNotIn('status', ['pending', 'cancelled', 'refunded']);
        $totalSalesAmount = $validSalesQuery->sum('total_price');
        
        $totalItemsQty = \App\Models\OrderItem::whereIn('order_id', $orderIds)->sum('quantity');

        $categorySalesAmount = 0;
        $categoryItemsQty = 0;
        if ($request->has('category_id') && !empty($request->category_id)) {
            $catId = $request->category_id;
            
            $validCategoryOrderIds = $validSalesQuery->pluck('id');
            
            $categorySalesAmount = \App\Models\OrderItem::whereIn('order_id', $validCategoryOrderIds)
                ->whereHas('product', function($q) use ($catId) {
                    $q->where('category_id', $catId);
                })->sum('total_price');

            $categoryItemsQty = \App\Models\OrderItem::whereIn('order_id', $orderIds)
                ->whereHas('product', function($q) use ($catId) {
                    $q->where('category_id', $catId);
                })->sum('quantity');
        }

        $limit = $request->input('limit', 20);
        $paginated = $query->paginate($limit);
        $paginatedArray = $paginated->toArray();
        $paginatedArray['summary'] = [
            'total_orders' => $totalOrdersCount,
            'total_sales' => $totalSalesAmount,
            'total_items_qty' => $totalItemsQty,
            'category_sales' => $categorySalesAmount,
            'category_items_qty' => $categoryItemsQty,
        ];

        return response()->json($paginatedArray);
    }

    public function show(string $id)
    {
        return Order::with(['items.product', 'user.dropshipperProfile', 'items.variation'])->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,confirmed,purchased_by_admin,ready_to_ship_bd,shipping,delivered,cancelled,refunded',
            'payment_status' => 'sometimes|in:unpaid,paid,partial',
            'tracking_id' => 'nullable|string',
            'shipping_cost' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'name' => 'sometimes|string',
            'email' => 'sometimes|nullable|email',
            'shipping_address' => 'sometimes|string',
            'contact_number' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string',
            'items' => 'sometimes|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.order_price' => 'nullable|numeric|min:0',
            'items.*.product_name' => 'nullable|string',
            'items.*.variation_snapshot' => 'nullable|string',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $validated) {
            $items = $validated['items'] ?? null;
            unset($validated['items']);

            // Update main order fields
            $order->update($validated);

            if ($items !== null) {
                // Delete existing items
                $order->items()->delete();

                // Re-create items and calculate subtotal
                $subtotal = 0;
                foreach ($items as $itemData) {
                    $product = \App\Models\Product::findOrFail($itemData['product_id']);
                    $unitPrice = $itemData['unit_price'] ?? ($product->sale_price ?? $product->base_price);
                    $quantity = $itemData['quantity'];
                    $totalPrice = $unitPrice * $quantity;
                    $subtotal += $totalPrice;

                    $orderPrice = $itemData['order_price'] ?? $unitPrice;

                    $variationId = null;
                    if (!empty($itemData['product_variation_id'])) {
                        if (\App\Models\ProductVariation::where('id', $itemData['product_variation_id'])->exists()) {
                            $variationId = $itemData['product_variation_id'];
                        }
                    }

                    $order->items()->create([
                        'product_id' => $itemData['product_id'],
                        'product_variation_id' => $variationId,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'purchase_price' => $product->purchase_price ?? 0,
                        'total_price' => $totalPrice,
                        'order_price' => $orderPrice,
                        'product_name' => $itemData['product_name'] ?? $product->name,
                        'variation_snapshot' => $itemData['variation_snapshot'] ?? null,
                    ]);
                }
                $order->subtotal = $subtotal;
            }

            // Recalculate total price
            $order->total_price = max(0, $order->subtotal - $order->discount + $order->shipping_cost);
            $order->save();

            if (isset($validated['status']) && $validated['status'] === 'delivered') {
                \App\Services\WalletService::distributeCommissions($order);
            }
        });

        return response()->json($order->load(['items.product', 'user.dropshipperProfile', 'items.variation']));
    }
}
