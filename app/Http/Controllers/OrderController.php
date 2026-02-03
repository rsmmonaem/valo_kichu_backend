<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\ProductVariation;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\AppliedCoupon;
use App\Models\Review;
use App\Models\PaymentInfo;
use App\Models\Wallet;
use Illuminate\Support\Facades\Validator;

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
            'name' => 'nullable|string', // Allow custom name
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $user = $request->user();
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                // ... (product/variation logic remains same)
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
                'total_price' => $total,
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
            $paymentResult = $paymentService->createPayment(
                $validated['payment_method'],
                $total,
                $validated['name'] ?? ($user->first_name . ' ' . $user->last_name), // Use custom name or fallback
                $user->email,
                $user->phone_number,
                $user
            );

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

        return response()->json(new OrderResource($order));
    }

        private function getItemPrice($product, $variant)
    {
        // If variant exists, use variant price
        if ($variant) {
            $price = (float) $variant->price;
            $discount = (float) $variant->discount;
            $discountType = $variant->discount_type;
            
            // If discount_price is already calculated, use it
            if ($variant->discount_price && $variant->discount_price > 0) {
                return (float) $variant->discount_price;
            }
            
            // Otherwise calculate based on discount_type
            if (in_array($discountType, ['percent', 'percentage']) && $discount > 0) {
                return $price * (100 - $discount) / 100;
            } elseif (in_array($discountType, ['amount', 'flat']) && $discount > 0) {
                return max(0, $price - $discount);
            }
            
            return $price;
        }
        
        // Use product price
        $price = (float) $product->price;
        $discount = (float) $product->discount;
        $discountType = $product->discount_type;
        
        // If discount_price is already calculated, use it
        if ($product->discount_price && $product->discount_price > 0) {
            return (float) $product->discount_price;
        }
        
        // Otherwise calculate based on discount_type
        if (in_array($discountType, ['percent', 'percentage']) && $discount > 0) {
            return $price * (100 - $discount) / 100;
        } elseif (in_array($discountType, ['amount', 'flat']) && $discount > 0) {
            return max(0, $price - $discount);
        }
        
        return $price;
    }

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variations,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $product = Product::findOrFail($request->item_id);
        $variant = $request->variant_id ? ProductVariation::find($request->variant_id) : null;
        
        $unitPrice = $this->getItemPrice($product, $variant);

        $cartItem = CartItem::firstOrNew(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'product_variation_id' => $variant?->id,
            ]
        );

        if ($cartItem->exists) {
            $cartItem->quantity = $request->quantity;
        } else {
            $cartItem->quantity = $request->quantity;
            $cartItem->added_at = now();
        }
        $cartItem->price = $unitPrice;
        $cartItem->save();
        $cartItem->load(['product.images', 'variation.images']);

        return response()->json(['message' => 'Item added to cart.', 'cart_item' => $cartItem], 201);
    }

    public function removeFromCart($item_id, Request $request)
    {
        $cartItem = CartItem::where('id', $item_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
        $cartItem->delete();
        return response()->json(['message' => 'Item removed from cart.'], 200);
    }

    public function updateCart($item_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer', // Allow -1, 0, +1 for increment/decrement
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $cartItem = CartItem::where('id', $item_id)
            ->where('user_id', $user->id)
            ->with(['product.images', 'variation.images'])
            ->firstOrFail();

        $cartItem->quantity += $request->quantity;
        
        if ($cartItem->quantity < 1) {
            $cartItem->quantity = 1;
        }
        
        $cartItem->save();
        
        return response()->json([
            'message' => 'Cart updated successfully.',
            'cart_item' => $cartItem
        ], 200);
    }

    public function cartList(Request $request)
    {
        $cartItems = CartItem::where('user_id', $request->user()->id)
            ->with(['product.images', 'variation.images'])
            ->get();

        return response()->json($cartItems);
    }

    public function applyCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'coupon_code' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $coupon = Coupon::where('code', $request->coupon_code)->first();

        if (!$coupon) {
            return response()->json(['detail' => 'Invalid coupon code.'], 400);
        }

        if (!$coupon->isValid()) {
            return response()->json(['detail' => 'Coupon is invalid or expired.'], 400);
        }

        if (CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return response()->json(['detail' => 'You have already used this coupon.'], 400);
        }

        if (AppliedCoupon::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return response()->json(['detail' => 'You already applied this coupon.'], 400);
        }

        AppliedCoupon::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
        ]);

        $discountInfo = [];
        if ($coupon->discount_rate) {
            $discountInfo['type'] = 'percentage';
            $discountInfo['value'] = (float) $coupon->discount_rate;
        } elseif ($coupon->discount_amount) {
            $discountInfo['type'] = 'amount';
            $discountInfo['value'] = (float) $coupon->discount_amount;
        }

        return response()->json([
            'message' => 'Coupon applied successfully.',
            'discount' => $discountInfo
        ], 200);
    }

    public function checkout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.variant_id' => 'nullable|exists:product_variations,id',
            'payment_method' => 'required|string',
            // 'address_id' => 'required|exists:address,id',
            'tran_id' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();

        // Fetch address details
        $address = null;
        if ($request->address_id && $user) {
            $address = Address::where('id', $request->address_id)
                ->where('user_id', $user->id)
                ->first();
        }

        // Align with Checkout.jsx keys: shipping_address, contact_number
        // Also fallback to address/phone_number if sent by others
        $shippingAddress = $address 
            ? "$address->address_line1, $address->city, $address->country" 
            : ($request->shipping_address ?? $request->address ?? 'N/A');
            
        $contactNumber = $address 
            ? $address->phone 
            : ($request->contact_number ?? $request->phone_number ?? ($user?->phone_number) ?? 'N/A');

        $tranId = $request->tran_id ?? $request->transaction_id;
        $payment = null;
        $paymentStatus = 'unpaid'; // default from migration

        if ($tranId) {
            $payment = PaymentInfo::where('transaction_id', $tranId)->first();
            if ($payment && $payment->status === PaymentInfo::STATUS_COMPLETE) {
                $paymentStatus = 'paid';
            }
        }

        // Calculate total
        $totalPrice = 0;
        $orderItems = [];

        foreach ($request->products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $variant = !empty($item['variant_id']) ? ProductVariation::find($item['variant_id']) : null;
            $quantity = (int) $item['quantity'];

            // Use standard price logic
            $itemPrice = $variant ? (float)$variant->price : (float)$product->price;

            $totalPrice += $itemPrice * $quantity;

            $orderItems[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $itemPrice
            ];
        }

        // Handle coupon (Only for logged-in users for now)
        $discountAmount = 0;
        $appliedCoupon = null;
        
        if ($user) {
            $appliedCoupon = AppliedCoupon::where('user_id', $user->id)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->first();

            if ($appliedCoupon) {
                $coupon = $appliedCoupon->coupon;

                if (CouponUsage::where('coupon_id', $coupon->id)
                    ->where('user_id', $user->id)
                    ->exists()) {
                    return response()->json(['detail' => 'You have already used this coupon.'], 400);
                }

                if (!$coupon->isValid()) {
                    return response()->json(['detail' => 'Coupon is no longer valid.'], 400);
                }

                if ($coupon->discount_rate) {
                    $discountAmount = ($totalPrice * $coupon->discount_rate / 100);
                } elseif ($coupon->discount_amount) {
                    $discountAmount = $coupon->discount_amount;
                }

                $totalPrice -= $discountAmount;
                if ($totalPrice < 0) {
                    $totalPrice = 0;
                }
            }
        }

        // Create Order
        $order = Order::create([
            'user_id' => $user ? $user->id : null,
            'name' => $request->name ?? ($user ? ($user->first_name . ' ' . $user->last_name) : 'Guest'),
            'subtotal' => $totalPrice + $discountAmount, // Before coupon
            'discount' => $discountAmount,
            'total_price' => $totalPrice, // You might want to add shipping cost here if passed from frontend
            'status' => 'pending',
            'payment_method' => $request->payment_method,
            'payment_status' => $paymentStatus,
            'transaction_id' => $tranId,
            'address_id' => $address?->id,
            'payment_id' => $payment?->id,
            'shipping_address' => $shippingAddress,
            'contact_number' => $contactNumber,
            'currency' => 'BDT',
            'exchange_rate' => 1,
            'shipping_cost' => $request->shipping_cost ?? 0, // Add shipping cost if passed
            'notes' => $request->notes ?? null,
        ]);
        
        // Note: I added shipping_cost and notes map because they were missing in original checkout logic 
        // but present in Checkout.jsx payload. Original used lines 87/94 in `store` but `checkout` missed them.
        // Wait, original `checkout` did NOT save shipping_cost or notes?
        // Let's check original.
        // Original `checkout` (314-469) did NOT map shipping_cost or notes from request to Order::create.
        // `Order::create` (418-433) only had those fields.
        // But `store` method (32-118) DID map them.
        // I should add them since the frontend sends them.

        // Create OrderItems
        foreach ($orderItems as $item) {
            $variationSnapshot = $item['variant'] 
                ? trim(($item['variant']->size ? "Size: {$item['variant']->size}, " : "") . ($item['variant']->color ? "Color: {$item['variant']->color}" : ""), ", ")
                : null;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_variation_id' => $item['variant']?->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total_price' => $item['price'] * $item['quantity'],
                'product_name' => $item['product']->name,
                'variation_snapshot' => $variationSnapshot,
            ]);
        }

        // Mark coupon as used
        if ($appliedCoupon && $user) {
            $appliedCoupon->is_used = true;
            $appliedCoupon->save();
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
            ]);
        }

        // Clear cart
        if ($user) {
            CartItem::where('user_id', $user->id)->delete();
        }

        $order->load(['items.product.images', 'items.variation.images']);

        return response()->json(new OrderResource($order), 201);
    }

    public function cancelOrder($order_id, Request $request)
    {
        $order = Order::where('id', $order_id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($order->status === 'Cancelled') {
            return response()->json(['detail' => 'Order is already cancelled.'], 400);
        }

        if (in_array($order->status, ['Delivered', 'Complete'])) {
            return response()->json(['detail' => 'Cannot cancel an order that is delivered or complete.'], 400);
        }

        $order->status = 'Cancelled';
        $order->save();

        return response()->json(['detail' => 'Order has been cancelled.'], 200);
    }

    public function orderInfoDetail($order_id, Request $request)
    {
        $order = Order::where('id', $order_id)
            ->where('user_id', $request->user()->id)
            ->with(['items.product.images', 'items.variation.images'])
            ->firstOrFail();

        return response()->json(new OrderResource($order));
    }

    public function ordersByStatus(Request $request)
    {
        $user = $request->user();
        $statusParam = $request->get('status', 'all');

        $orders = Order::where('user_id', $user->id);

        if ($statusParam !== 'all') {
            $orders->where('status', $statusParam);
        }

        $orders = $orders->orderBy('created_at', 'desc')
            ->with(['items.product.images', 'items.variation.images'])
            ->get();

        return response()->json(OrderResource::collection($orders));
    }

    public function postReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product' => 'required|exists:products,id',
            'comment' => 'required|string|max:250',
            'rating' => 'required|integer|min:1|max:5',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $product = Product::findOrFail($request->product);

        // Check if user has delivered orders with this product
        $deliveredOrders = Order::where('user_id', $user->id)
            ->where('status', 'Delivered')
            ->pluck('id');

        $deliveredOrderItems = OrderItem::whereIn('order_id', $deliveredOrders)
            ->where('product_id', $product->id)
            ->exists();

        if (!$deliveredOrderItems) {
            return response()->json([
                'error' => 'You can only review products you have received.'
            ], 403);
        }

        // Prevent duplicate reviews
        if (Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists()) {
            return response()->json([
                'error' => 'You have already reviewed this product.'
            ], 400);
        }

        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'comment' => $request->comment,
            'rating' => $request->rating,
            'image' => $request->hasFile('image') 
                ? $request->file('image')->store('reviews', 'public') 
                : null,
        ]);

        return response()->json($review, 201);
    }

    public function getReviews($product_id)
    {
        $product = Product::findOrFail($product_id);
        $reviews = Review::where('product_id', $product_id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_id' => $review->user_id,
                    'product_id' => $review->product_id,
                    'comment' => $review->comment,
                    'image' => $review->image_url, // Use accessor for full URL
                    'rating' => $review->rating,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'first_name' => $review->user->first_name,
                        'last_name' => $review->user->last_name,
                        'email' => $review->user->email,
                        'phone_number' => $review->user->phone_number,
                        'image' => $review->user->image_url, // Use accessor for full URL
                    ] : null,
                    'created_at' => $review->created_at?->toDateTimeString(),
                    'updated_at' => $review->updated_at?->toDateTimeString(),
                ];
            });

        return response()->json($reviews);
    }
}
