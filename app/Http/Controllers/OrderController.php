<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($request->user()->orders()->latest()->paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|string',
            'contact_number' => 'required|string',
            'notes' => 'nullable|string',
            'payment_method' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $product->sale_price ?? $product->base_price;
                $variationSnapshot = null;

                if (!empty($item['product_variation_id'])) {
                    $variation = $product->variations()->find($item['product_variation_id']);
                    if ($variation) {
                        $price += $variation->price_modifier;
                        $variationSnapshot = "Size: {$variation->size}, Color: {$variation->color}";
                    }
                }

                $lineTotal = $price * $item['quantity'];
                $subtotal += $lineTotal;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_variation_id' => $item['product_variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $price,
                    'total_price' => $lineTotal,
                    'product_name' => $product->name,
                    'variation_snapshot' => $variationSnapshot,
                ];
            }

            // Simple Shipping Logic (Mock)
            $shippingCost = 100; // Flat rate for MVP
            $total = $subtotal + $shippingCost;

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total_amount' => $total,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $validated['payment_method'],
                'shipping_address' => $validated['shipping_address'],
                'contact_number' => $validated['contact_number'],
                'notes' => $validated['notes'] ?? null,
                'currency' => 'BDT', 
            ]);

            foreach ($itemsData as $data) {
                $order->items()->create($data);
            }

            // Process Payment
            $paymentService = new \App\Services\PaymentService();
            $paymentResult = $paymentService->processPayment($validated['payment_method'], [
                'order_number' => $order->order_number,
                'customer_email' => $user->email,
            ], $total);

            return response()->json([
                'order' => $order->load('items'),
                'payment_result' => $paymentResult
            ], 201);
        });
    }

    public function show(string $id, Request $request)
    {
        $order = Order::with(['items', 'items.product'])->findOrFail($id);
        
        if ($request->user()->id !== $order->user_id) {
            abort(403);
        }

        return response()->json($order);
    }
}
