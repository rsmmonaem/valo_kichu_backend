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

        if ($request->has('courier_name') && !empty($request->courier_name)) {
            if ($request->courier_name === 'unassigned') {
                $query->whereNull('courier_name');
            } else {
                $query->where('courier_name', $request->courier_name);
            }
        }

        if ($request->has('page_name') && !empty($request->page_name)) {
            $query->where('page_name', $request->page_name);
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
        $validSalesQuery->whereNotIn('status', ['pending', 'cancelled', 'refunded', 'returned']);
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

        // Build status counts query (all filters except status)
        $statusCountsQuery = Order::query();

        if ($request->has('order_type')) {
            if ($request->order_type === 'customer') {
                $statusCountsQuery->whereIn('order_type', ['direct', 'referral']);
            } elseif ($request->order_type === 'dropshipper' || $request->order_type === 'dropshipping') {
                $statusCountsQuery->where('order_type', 'dropshipping');
            } else {
                $statusCountsQuery->where('order_type', $request->order_type);
            }
        }

        if ($request->has('start_date') && !empty($request->start_date)) {
            $start = \Carbon\Carbon::parse($request->start_date, 'Asia/Dhaka')->startOfDay()->setTimezone('UTC');
            $statusCountsQuery->where('created_at', '>=', $start);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $end = \Carbon\Carbon::parse($request->end_date, 'Asia/Dhaka')->endOfDay()->setTimezone('UTC');
            $statusCountsQuery->where('created_at', '<=', $end);
        }

        if ($request->has('courier_name') && !empty($request->courier_name)) {
            if ($request->courier_name === 'unassigned') {
                $statusCountsQuery->whereNull('courier_name');
            } else {
                $statusCountsQuery->where('courier_name', $request->courier_name);
            }
        }

        if ($request->has('page_name') && !empty($request->page_name)) {
            $statusCountsQuery->where('page_name', $request->page_name);
        }

        if ($request->has('category_id') && !empty($request->category_id)) {
            $statusCountsQuery->whereHas('items.product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $statusCountsQuery->where(function($q) use ($searchTerm) {
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

        $rawCounts = (clone $statusCountsQuery)
            ->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $totalAll = array_sum($rawCounts);

        $statusCounts = [
            'all' => $totalAll,
            'pending' => $rawCounts['pending'] ?? 0,
            'contacted' => $rawCounts['contacted'] ?? 0,
            'confirmed' => $rawCounts['confirmed'] ?? 0,
            'purchased_by_admin' => $rawCounts['purchased_by_admin'] ?? 0,
            'ready_to_ship_bd' => $rawCounts['ready_to_ship_bd'] ?? 0,
            'shipping' => $rawCounts['shipping'] ?? 0,
            'delivered' => $rawCounts['delivered'] ?? 0,
            'cancelled' => $rawCounts['cancelled'] ?? 0,
            'refunded' => $rawCounts['refunded'] ?? 0,
            'transfer_to_courier' => $rawCounts['transfer_to_courier'] ?? 0,
            'returned' => $rawCounts['returned'] ?? 0,
        ];

        $limit = $request->input('limit', $request->input('per_page', 20));
        $paginated = $query->paginate($limit);
        $paginatedArray = $paginated->toArray();
        $paginatedArray['summary'] = [
            'total_orders' => $totalOrdersCount,
            'total_sales' => $totalSalesAmount,
            'total_items_qty' => $totalItemsQty,
            'category_sales' => $categorySalesAmount,
            'category_items_qty' => $categoryItemsQty,
        ];
        $paginatedArray['status_counts'] = $statusCounts;

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
            'status' => 'sometimes|in:pending,contacted,confirmed,purchased_by_admin,ready_to_ship_bd,shipping,delivered,cancelled,refunded,transfer_to_courier,returned',
            'payment_status' => 'sometimes|in:unpaid,paid,partial',
            'tracking_id' => 'nullable|string',
            'shipping_cost' => 'sometimes|numeric|min:0',
            'discount' => 'sometimes|numeric|min:0',
            'name' => 'sometimes|string',
            'email' => 'sometimes|nullable|email',
            'shipping_address' => 'sometimes|string',
            'contact_number' => 'sometimes|string',
            'notes' => 'sometimes|nullable|string',
            'call_status' => 'sometimes|nullable|string',
            'last_called_at' => 'sometimes|nullable|date',
            'next_call_at' => 'sometimes|nullable|date',
            'crm_logs' => 'sometimes|nullable|array',
            'page_name' => 'sometimes|nullable|string',
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

    public function customerHistory(Request $request)
    {
        $phone = trim($request->query('phone') ?? $request->query('contact_number') ?? '');
        $email = trim($request->query('email') ?? '');
        $excludeId = $request->query('exclude_id');

        if (in_array(strtolower($phone), ['', 'n/a', 'null', 'undefined'])) {
            $phone = null;
        }
        if (in_array(strtolower($email), ['', 'n/a', 'null', 'undefined'])) {
            $email = null;
        }

        if (!$phone && !$email) {
            return response()->json([]);
        }

        $query = Order::with(['items.product'])->latest();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($phone, $email) {
            $hasCondition = false;
            if ($phone) {
                $q->where('contact_number', $phone);
                $hasCondition = true;
            }
            if ($email) {
                if ($hasCondition) {
                    $q->orWhere('email', $email);
                } else {
                    $q->where('email', $email);
                }
            }
        });

        $orders = $query->take(20)->get();

        return response()->json($orders);
    }

    public function addCrmLog(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'call_status' => 'required|string',
            'last_called_at' => 'nullable|date',
            'next_call_at' => 'nullable|date',
            'note' => 'nullable|string',
        ]);

        $logs = $order->crm_logs ?? [];
        if (!is_array($logs)) {
            $logs = [];
        }

        $calledAt = !empty($validated['last_called_at'])
            ? \Carbon\Carbon::parse($validated['last_called_at'])->toIso8601String()
            : now()->toIso8601String();

        $nextCallAt = !empty($validated['next_call_at'])
            ? \Carbon\Carbon::parse($validated['next_call_at'])->toIso8601String()
            : null;

        $newLog = [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'call_status' => $validated['call_status'],
            'called_at' => $calledAt,
            'next_call_at' => $nextCallAt,
            'note' => $validated['note'] ?? '',
            'created_at' => now()->toIso8601String(),
            'created_by' => auth()->user()->name ?? 'Admin',
        ];

        array_unshift($logs, $newLog);

        $order->call_status = $validated['call_status'];
        $order->last_called_at = $calledAt;
        if ($nextCallAt) {
            $order->next_call_at = $nextCallAt;
        }
        $order->crm_logs = $logs;
        $order->save();

        return response()->json([
            'message' => 'CRM call log added successfully',
            'order' => $order->fresh(['items.product', 'user.dropshipperProfile', 'items.variation'])
        ]);
    }

    public function sendCourier(Request $request, string $id)
    {
        $order = Order::with('user')->findOrFail($id);

        $validated = $request->validate([
            'courier_name' => 'required|string',
            'recipient_name' => 'nullable|string',
            'recipient_phone' => 'nullable|string',
            'recipient_address' => 'nullable|string',
            'cod_amount' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);

        $courierName = strtolower($validated['courier_name']);

        if ($courierName === 'steadfast') {
            $steadfastService = new \App\Services\SteadfastService();

            // Extract shipping address clean text
            $recipientAddress = $validated['recipient_address'] ?? null;
            if (empty($recipientAddress)) {
                try {
                    $addr = json_decode($order->shipping_address, true);
                    if ($addr && is_array($addr)) {
                        $recipientAddress = implode(', ', array_filter([
                            $addr['address'] ?? '',
                            $addr['area'] ?? '',
                            $addr['city'] ?? ''
                        ]));
                    } else {
                        $recipientAddress = $order->shipping_address;
                    }
                } catch (\Exception $e) {
                    $recipientAddress = $order->shipping_address;
                }
            }

            $recipientName = $validated['recipient_name'] ?? ($order->name ?: ($order->user?->name ?? 'Customer'));
            $recipientPhone = $validated['recipient_phone'] ?? ($order->contact_number ?: ($order->phone ?: ''));
            $codAmount = isset($validated['cod_amount']) ? (float)$validated['cod_amount'] : (float)$order->total_price;
            $note = $validated['note'] ?? ($order->notes ?? '');

            $result = $steadfastService->createOrder([
                'invoice' => $order->order_number ?: (string)$order->id,
                'recipient_name' => $recipientName,
                'recipient_phone' => $recipientPhone,
                'recipient_address' => $recipientAddress,
                'cod_amount' => $codAmount,
                'note' => $note,
            ]);

            if ($result['success']) {
                $order->courier_name = 'Steadfast';
                $order->courier_consignment_id = $result['consignment_id'] ?? null;
                $order->courier_status = $result['status'] ?? 'in_review';
                if (!empty($result['tracking_code'])) {
                    $order->tracking_id = $result['tracking_code'];
                }
                $order->status = 'transfer_to_courier';
                $order->save();

                return response()->json([
                    'message' => 'Order successfully sent to Steadfast Courier!',
                    'order' => $order->fresh(['items.product', 'user.dropshipperProfile', 'items.variation']),
                    'result' => $result,
                ]);
            } else {
                return response()->json([
                    'message' => $result['message'] ?? 'Failed to send order to Steadfast Courier.',
                    'result' => $result,
                ], 400);
            }
        }

        if ($courierName === 'self' || $courierName === 'self_delivery') {
            $order->courier_name = 'Self Delivery';
            $order->courier_status = 'confirmed';
            $order->status = 'transfer_to_courier';
            $order->save();

            return response()->json([
                'message' => 'Order status updated to Transferred to Courier (Self Delivery)!',
                'order' => $order->fresh(['items.product', 'user.dropshipperProfile', 'items.variation']),
            ]);
        }

        return response()->json([
            'message' => "Courier '{$validated['courier_name']}' is not supported currently.",
        ], 400);
    }

    public function refund(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'refund_items' => 'required|array',
            'refund_items.*.item_id' => 'required|exists:order_items,id',
            'refund_items.*.refund_quantity' => 'required|integer|min:0',
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $validated) {
            foreach ($validated['refund_items'] as $refundData) {
                $item = \App\Models\OrderItem::where('order_id', $order->id)->findOrFail($refundData['item_id']);
                
                if ($refundData['refund_quantity'] > $item->quantity) {
                    throw new \InvalidArgumentException("Refund quantity cannot exceed ordered quantity for item: " . $item->product_name);
                }
                
                $item->refunded_quantity = $refundData['refund_quantity'];
                $item->save();
            }

            $order->status = 'refunded';
            $order->save();
        });

        return response()->json([
            'message' => 'Order items refunded successfully',
            'order' => $order->fresh(['items.product', 'user.dropshipperProfile', 'items.variation'])
        ]);
    }
}
