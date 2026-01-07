<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\AppliedCoupon;
use App\Models\Review;
use App\Models\PaymentInfo;
use App\Models\Wallet;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrderController extends Controller
{

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
            'variant_id' => 'nullable|exists:variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $product = Product::findOrFail($request->item_id);
        $variant = $request->variant_id ? Variant::find($request->variant_id) : null;
        
        $unitPrice = $this->getItemPrice($product, $variant);

        $cartItem = CartItem::firstOrNew(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
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
        $cartItem = $cartItem->with(['product.images', 'variant'])->first();

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
            ->with(['product', 'variant'])
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
            ->with(['product.images', 'variant'])
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
            'products.*.variant_id' => 'nullable|exists:variants,id',
            'payment_method' => 'required|string',
            // 'address_id' => 'required|exists:address,id',
            'tran_id' => 'nullable|string',
            'transaction_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        // $address = Address::where('id', $request->address_id)
        //     ->where('user_id', $user->id)
        //     ->firstOrFail();

        $tranId = $request->tran_id ?? $request->transaction_id;
        $payment = null;
        $paymentStatus = 'Pending';

        if ($tranId) {
            $payment = PaymentInfo::where('transaction_id', $tranId)->first();
            if ($payment && $payment->status === PaymentInfo::STATUS_COMPLETE) {
                $paymentStatus = 'Complete';
            }
        }

        // Calculate total
        $totalPrice = 0;
        $orderItems = [];

        foreach ($request->products as $item) {
            $product = Product::findOrFail($item['product_id']);
            $variant = $item['variant_id'] ? Variant::find($item['variant_id']) : null;
            $quantity = (int) $item['quantity'];

            $itemPrice = $variant 
                ? ($variant->discount_price > 0 ? $variant->discount_price : $variant->price)
                : ($product->discount_price > 0 ? $product->discount_price : $product->price);

            $totalPrice += $itemPrice * $quantity;

            $orderItems[] = [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $quantity,
                'price' => $itemPrice
            ];
        }

        // Handle coupon
        $discountAmount = 0;
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

        // Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'total_price' => $totalPrice,
            'status' => 'Pending',
            'payment_method' => $request->payment_method,
            'payment_status' => $paymentStatus,
            'transaction_id' => $tranId,
            // 'address_id' => $address->id,
            'payment_id' => $payment?->id,
        ]);

        // Create OrderItems and update vendor wallets
        foreach ($orderItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'variant_id' => $item['variant']?->id,
                'quantity' => $item['quantity'],
                'price' => $item['price']
            ]);

            // Update vendor wallet
            if ($item['product']->vendor_id) {
                $wallet = Wallet::firstOrCreate(['vendor_id' => $item['product']->vendor_id]);
                $earnedAmount = $item['price'] * $item['quantity'];
                $wallet->main_balance += $earnedAmount;
                $wallet->save();
            }
        }

        // Mark coupon as used
        if ($appliedCoupon) {
            $appliedCoupon->is_used = true;
            $appliedCoupon->save();
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user->id,
            ]);
        }

        // Clear cart
        CartItem::where('user_id', $user->id)->delete();

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
            ->with(['orderItems.product', 'orderItems.variant', 'address', 'payment'])
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
            ->with(['orderItems.product', 'orderItems.variant', 'address'])
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
