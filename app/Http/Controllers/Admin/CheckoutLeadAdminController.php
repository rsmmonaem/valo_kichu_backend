<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutLead;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CheckoutLeadAdminController extends Controller
{
    /**
     * List all checkout leads with optional filters.
     */
    public function index(Request $request)
    {
        $query = CheckoutLead::with('user:id,first_name,last_name,email')
            ->orderByDesc('updated_at');

        // Filter by conversion status
        if ($request->has('converted')) {
            $query->where('converted', filter_var($request->converted, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate($request->input('per_page', 20));

        return response()->json($leads);
    }

    /**
     * Delete a single lead.
     */
    public function destroy($id)
    {
        CheckoutLead::findOrFail($id)->delete();
        return response()->json(['status' => true, 'message' => 'Lead deleted.']);
    }

    /**
     * Stats summary.
     */
    public function stats()
    {
        return response()->json([
            'total'     => CheckoutLead::count(),
            'converted' => CheckoutLead::where('converted', true)->count(),
            'pending'   => CheckoutLead::where('converted', false)->count(),
            'today'     => CheckoutLead::whereDate('created_at', today())->count(),
        ]);
    }

    /**
     * Convert a checkout lead to an order.
     */
    public function convert($id, Request $request)
    {
        $lead = CheckoutLead::findOrFail($id);

        if ($lead->converted) {
            return response()->json([
                'status' => false,
                'message' => 'This lead is already converted to an order #' . $lead->order_id
            ], 422);
        }

        if (empty($lead->cart_data)) {
            return response()->json([
                'status' => false,
                'message' => 'Cart is empty for this lead.'
            ], 422);
        }

        $name = $request->input('name', $lead->name) ?: 'Guest';
        $phone = $request->input('phone', $lead->phone) ?: 'N/A';
        $email = $request->input('email', $lead->email);
        $address = $request->input('address', $lead->address) ?: 'N/A';
        $area = $request->input('area', $lead->area);
        $paymentMethod = $request->input('payment_method', $lead->payment_method) ?: 'cod';
        $notes = $request->input('notes', $lead->notes);
        
        $shippingCost = $request->input('shipping_cost');
        if ($shippingCost === null) {
            $shippingCost = 0;
            if ($area) {
                $method = \App\Models\ShippingMethod::where('name', $area)->first();
                if ($method) {
                    $shippingCost = $method->cost;
                }
            }
        }

        try {
            $order = DB::transaction(function () use ($lead, $name, $phone, $email, $address, $area, $paymentMethod, $notes, $shippingCost) {
                $subtotal = 0;
                $orderItems = [];

                foreach ($lead->cart_data as $item) {
                    $product = Product::find($item['product_id']);
                    if (!$product) {
                        throw new \Exception("Product with ID {$item['product_id']} not found or has been deleted.");
                    }

                    $quantity = (int) $item['quantity'];
                    $price = isset($item['price']) && (float)$item['price'] > 0
                        ? (float)$item['price']
                        : ($product->sale_price ?? $product->base_price ?? 0);

                    $lineTotal = $price * $quantity;
                    $subtotal += $lineTotal;

                    $variationId = $item['product_variation_id'] ?? null;
                    $variation = null;
                    if ($variationId) {
                        $variation = \App\Models\ProductVariation::find($variationId);
                    }

                    $variationSnapshot = $item['variation_snapshot'] ?? null;
                    if (!$variationSnapshot && $variation) {
                        $variationSnapshot = trim(($variation->size ? "Size: {$variation->size}, " : "") . ($variation->color ? "Color: {$variation->color}" : ""), ", ");
                    }

                    $orderItems[] = [
                        'product' => $product,
                        'variant' => $variation,
                        'quantity' => $quantity,
                        'price' => $price,
                        'variation_snapshot' => $variationSnapshot,
                    ];
                }

                $totalPrice = $subtotal;
                $discountAmount = 0;

                $fullAddress = $address;
                if ($area && stripos($address, $area) === false) {
                    $fullAddress .= ", " . $area;
                }

                $order = Order::create([
                    'user_id' => $lead->user_id,
                    'name' => $name,
                    'email' => $email,
                    'subtotal' => $totalPrice,
                    'discount' => $discountAmount,
                    'total_price' => $totalPrice + $shippingCost,
                    'status' => 'pending',
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'unpaid',
                    'shipping_address' => $fullAddress,
                    'contact_number' => $phone,
                    'currency' => 'BDT',
                    'exchange_rate' => 1,
                    'shipping_cost' => $shippingCost,
                    'notes' => $notes,
                    'order_type' => ($lead->user_id && \App\Models\User::find($lead->user_id)?->isAnyDropshipper()) ? 'dropshipping' : 'direct',
                ]);

                foreach ($orderItems as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'product_variation_id' => $item['variant']?->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['price'],
                        'purchase_price' => $item['product']->purchase_price ?? 0,
                        'total_price' => $item['price'] * $item['quantity'],
                        'product_name' => $item['product']->name,
                        'variation_snapshot' => $item['variation_snapshot'],
                    ]);
                }

                $lead->update([
                    'converted' => true,
                    'order_id' => $order->id,
                ]);

                return $order;
            });

            return response()->json([
                'status' => true,
                'message' => 'Lead successfully converted to Order #' . $order->id,
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to convert lead to order: ' . $e->getMessage()
            ], 500);
        }
    }
}
