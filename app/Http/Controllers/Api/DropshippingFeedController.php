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
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * Place an order via API
     */
    public function placeOrder(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.phone' => 'required|string',
            'shipping_address.address' => 'required|string',
            'shipping_address.city' => 'required|string',
            'variation_id' => 'nullable|exists:product_variations,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::find($request->product_id);
        $dropshipperPrice = $product->getCurrentPriceForUser($user);
        $totalAmount = $dropshipperPrice * $request->quantity;

        // Check if user has enough balance (optional, based on requirement "Wallet system")
        // If we want to allow credit orders, we can skip this or track it.
        
        try {
            DB::beginTransaction();

            $order = Order::create([
                'user_id' => $user->id,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'shipping_address' => json_encode($request->shipping_address),
                'order_type' => 'dropshipping',
                'payment_status' => 'unpaid',
            ]);

            $product = Product::find($request->product_id);
            $variation = $request->variation_id ? \App\Models\ProductVariation::find($request->variation_id) : null;
            
            $variationSnapshot = $request->variation_snapshot;
            if (!$variationSnapshot && $variation) {
                $variationSnapshot = trim(($variation->size ? "Size: {$variation->size}, " : "") . ($variation->color ? "Color: {$variation->color}" : ""), ", ");
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_variation_id' => $request->variation_id,
                'quantity' => $request->quantity,
                'unit_price' => $dropshipperPrice,
                'purchase_price' => $product->purchase_price ?? 0,
                'total_price' => $totalAmount,
                'product_name' => $product->name,
                'variation_snapshot' => $variationSnapshot,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully.',
                'order_id' => $order->id,
                'total_amount' => $totalAmount
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to place order: ' . $e->getMessage()
            ], 500);
        }
    }
}
