<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DropshippingFeedController extends Controller
{
    /**
     * Get products with calculated prices for the authenticated dropshipper
     */
    public function getProducts(Request $request)
    {
        $user = auth()->user();
        $query = $request->input('q');
        
        $products = Product::where('is_active', true)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%")
                        ->orWhere('product_code', 'like', "%{$query}%");
                });
            })
            ->paginate(200);

        $products->getCollection()->transform(function ($product) use ($user) {
            return [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'base_price' => $product->base_price,
                'your_price' => $product->getCurrentPriceForUser($user),
                'stock' => $product->current_stock,
                'images' => $product->image_url,
                'gallery' => $product->gallery_image_urls,
                'variations' => $product->variations,
                'product_code' => $product->product_sku,
                'specifications' => $product->specifications,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Get a single product with calculated price
     */
    public function show(Request $request, $id)
    {
        $user = auth()->user();
        $product = Product::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'base_price' => $product->base_price,
                'your_price' => $product->getCurrentPriceForUser($user),
                'stock' => $product->stock_quantity,
                'images' => $product->image_url,
                'gallery' => $product->gallery_image_urls,
                'variations' => $product->variations,
                'product_code' => $product->product_code,
                'specifications' => $product->specifications,
            ]
        ]);
    }

    /**
     * Place an order via API
     */
    public function placeOrder(Request $request)
    {
        $user = auth()->user();

        $input = $request->all();
        
        // Normalize single product to products array for uniform processing
        if (!isset($input['products']) && isset($input['product_id'])) {
            $input['products'] = [
                [
                    'product_id' => $input['product_id'],
                    'quantity' => $input['quantity'] ?? 1,
                    'variation_id' => $input['variation_id'] ?? null,
                    'variation_snapshot' => $input['variation_snapshot'] ?? null,
                ]
            ];
        }

        $validator = Validator::make($input, [
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.variation_id' => 'nullable|exists:product_variations,id',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_method_id' => 'nullable|exists:shipping_methods,id',
            'shipping_method' => 'nullable|string',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $totalPrice = 0;
            $orderItemsData = [];

            foreach ($input['products'] as $item) {
                $product = Product::find($item['product_id']);
                $dropshipperPrice = (float) $product->getCurrentPriceForUser($user);
                $quantity = (int) $item['quantity'];
                $lineTotal = $dropshipperPrice * $quantity;

                $order_price=$item['order_price'] ?? null; // Use provided order_price or calculate from dropshipper price
                
                $variation = !empty($item['variation_id']) ? \App\Models\ProductVariation::find($item['variation_id']) : null;
                $variationSnapshot = $item['variation_snapshot'] ?? null;
                
                if (!$variationSnapshot && $variation) {
                    $variationSnapshot = trim(($variation->size ? "Size: {$variation->size}, " : "") . ($variation->color ? "Color: {$variation->color}" : ""), ", ");
                }

                $totalPrice += $lineTotal;
                
                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'product_variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $dropshipperPrice,
                    'purchase_price' => $product->purchase_price ?? 0,
                    'total_price' => $lineTotal,
                    'order_price' => $order_price ?? null, 
                    'product_name' => $product->name,
                    'variation_snapshot' => $variationSnapshot,
                ];
            }

            $shippingCost = 0;
            if (!empty($input['shipping_method_id'])) {
                $shippingMethod = \App\Models\ShippingMethod::find($input['shipping_method_id']);
                if ($shippingMethod && $shippingMethod->is_active) {
                    $shippingCost = (float) $shippingMethod->cost;
                }
            } elseif (!empty($input['shipping_method'])) {
                $shippingMethod = \App\Models\ShippingMethod::where('is_active', true)
                    ->where('name', 'like', '%' . $input['shipping_method'] . '%')
                    ->first();
                if ($shippingMethod) {
                    $shippingCost = (float) $shippingMethod->cost;
                }
            } elseif (isset($input['shipping_cost'])) {
                $shippingCost = (float) $input['shipping_cost'];
            }

            $order = Order::create([
                'user_id' => $user->id,
                'name' => $input['shipping_address']['name'] ?? null,
                'total_price' => $totalPrice + $shippingCost,
                'order_price' => $totalPrice + $shippingCost,
                'subtotal' => $totalPrice,
                'shipping_cost' => $shippingCost,
                'status' => 'pending',
                'shipping_address' => json_encode($input['shipping_address']),
                'order_type' => 'dropshipping',
                'payment_status' => 'unpaid',
                'contact_number' => $input['shipping_address']['phone'] ?? '+880000000000',
                'currency' => 'BDT',
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);

                // Deduct Product Stock for non-Mohasagor products
                $productToUpdate = Product::find($itemData['product_id']);
                if ($productToUpdate && $productToUpdate->api_from !== 'mohasagor') {
                    if ($productToUpdate->current_stock !== null) {
                        $productToUpdate->decrement('current_stock', $itemData['quantity']);
                    }
                    if ($productToUpdate->stock_quantity !== null) {
                        $productToUpdate->decrement('stock_quantity', $itemData['quantity']);
                    }
                    
                    // Deduct Variation Stock
                    if (!empty($itemData['product_variation_id'])) {
                        $variantToUpdate = \App\Models\ProductVariation::find($itemData['product_variation_id']);
                        if ($variantToUpdate && $variantToUpdate->stock_quantity !== null) {
                            $variantToUpdate->decrement('stock_quantity', $itemData['quantity']);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully.',
                'order_id' => $order->id,
                'total_amount' => $totalPrice + $shippingCost
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to place order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check wallet balance via API
     */
    public function getBalance()
    {
        $user = auth()->user();
        $balance = \App\Models\WalletTransaction::where('user_id', $user->id)
            ->where('type', 'credit')
            ->sum('amount');

        return response()->json([
            'status' => 'success',
            'data' => [
                'balance' => $balance,
                'currency' => 'BDT'
            ]
        ]);
    }

    /**
     * Get active categories with optional parent filter and subcategories
     */
    public function getCategories(Request $request)
    {
        $parentOnly = $request->boolean('parent_only', false);

        $query = \App\Models\Category::where('is_active', true);

        if ($parentOnly) {
            $query->whereNull('parent_id');
        }

        $categories = $query->with(['children' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('priority', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    /**
     * Get order history for the authenticated dropshipper
     */
    public function getOrders(Request $request)
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)
            ->with(['items', 'items.product'])
            ->latest()
            ->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }
}
