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
use App\Models\User;
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
            'items.*.product_variation_id' => 'nullable|integer',
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

            // Calculate cumulative quantities per product_id
            $productQuantities = [];
            foreach ($validated['items'] as $item) {
                $pid = $item['product_id'];
                $qty = (int) $item['quantity'];
                $productQuantities[$pid] = ($productQuantities[$pid] ?? 0) + $qty;
            }

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $price = $product->sale_price ?? $product->base_price;
                $variationSnapshot = null;

                $variation = null;
                if (!empty($item['product_variation_id'])) {
                    $variation = $product->variations()->find($item['product_variation_id']);
                    if ($variation) {
                        $price += $variation->price_modifier;
                        $variationSnapshot = "Size: {$variation->size}, Color: {$variation->color}";
                    }
                }

                // Check Stock Before Proceeding
                if ($product->api_from !== 'mohasagor') {
                    $quantity = $item['quantity'];
                    if ($variation && $variation->stock_quantity !== null) {
                        if ($variation->stock_quantity < $quantity) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['stock' => "Insufficient stock for {$product->name}. Available: {$variation->stock_quantity}"]);
                        }
                    } else {
                        $availableStock = $product->current_stock ?? $product->stock_quantity;
                        if ($availableStock !== null && $availableStock < $quantity) {
                            throw \Illuminate\Validation\ValidationException::withMessages(['stock' => "Insufficient stock for {$product->name}. Available: {$availableStock}"]);
                        }
                    }
                }

                // Apply dynamic bulk discount rules if any
                $cumulativeQty = $productQuantities[$product->id] ?? 0;
                $bulkDiscountPerItem = 0;
                if (!empty($product->bulk_discount_rules) && is_array($product->bulk_discount_rules)) {
                    foreach ($product->bulk_discount_rules as $rule) {
                        $minQty = isset($rule['min_qty']) ? (int) $rule['min_qty'] : 0;
                        $discAmt = isset($rule['discount_amount']) ? (float) $rule['discount_amount'] : 0;
                        if ($minQty > 0 && $cumulativeQty >= $minQty) {
                            $bulkDiscountPerItem = max($bulkDiscountPerItem, $discAmt);
                        }
                    }
                }

                if ($bulkDiscountPerItem > 0) {
                    $price = max(0, $price - $bulkDiscountPerItem);
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

                // Deduct Product Stock
                $productToUpdate = Product::find($data['product_id']);
                if ($productToUpdate && $productToUpdate->api_from !== 'mohasagor') {
                    if ($productToUpdate->current_stock !== null) {
                        $productToUpdate->decrement('current_stock', $data['quantity']);
                    }
                    if ($productToUpdate->stock_quantity !== null) {
                        $productToUpdate->decrement('stock_quantity', $data['quantity']);
                    }

                    // Deduct Variation Stock
                    if (!empty($data['product_variation_id'])) {
                        $variantToUpdate = ProductVariation::find($data['product_variation_id']);
                        if ($variantToUpdate && $variantToUpdate->stock_quantity !== null) {
                            $variantToUpdate->decrement('stock_quantity', $data['quantity']);
                        }
                    }
                }
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

        if (
            CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists()
        ) {
            return response()->json(['detail' => 'You have already used this coupon.'], 400);
        }

        if (
            AppliedCoupon::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->exists()
        ) {
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
            'products.*.variant_id' => 'nullable|integer',
            'products.*.product_variation_id' => 'nullable|integer', // also accept this key
            'products.*.price' => 'nullable|numeric|min:0', // frontend-sent price
            'payment_method' => 'required|string',
            // 'address_id' => 'required|exists:address,id',
            'tran_id' => 'nullable|string',
            'transaction_id' => 'nullable|string',
            'referral_code' => 'nullable|string',
            'referral_source' => 'nullable|string',
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

        // Referral tracking
        $referrer = null;
        if ($request->referral_code) {
            $referrer = User::where('refer_code', $request->referral_code)->first();
        }

        try {
            DB::beginTransaction();

            // Calculate total
            $totalPrice = 0;
            $orderItems = [];

            // Calculate cumulative quantities per product_id
            $productQuantities = [];
            foreach ($request->products as $item) {
                $pid = $item['product_id'];
                $qty = (int) $item['quantity'];
                $productQuantities[$pid] = ($productQuantities[$pid] ?? 0) + $qty;
            }

            foreach ($request->products as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                // Accept both 'variant_id' and 'product_variation_id' keys from frontend
                $variantId = $item['variant_id'] ?? $item['product_variation_id'] ?? null;
                $variant = !empty($variantId) ? ProductVariation::find($variantId) : null;
                $quantity = (int) $item['quantity'];

                // Stock Check Before Calculating Totals
                if ($product->api_from !== 'mohasagor') {
                    if ($variant && $variant->stock_quantity !== null) {
                        if ($variant->stock_quantity < $quantity) {
                            DB::rollBack();
                            return response()->json(['errors' => ['stock' => ["Insufficient stock for {$product->name}. Available: {$variant->stock_quantity}"]]], 400);
                        }
                    } else {
                        $availableStock = $product->current_stock ?? $product->stock_quantity;
                        if ($availableStock !== null && $availableStock < $quantity) {
                            DB::rollBack();
                            return response()->json(['errors' => ['stock' => ["Insufficient stock for {$product->name}. Available: {$availableStock}"]]], 400);
                        }
                    }
                }

                // Frontend sends the actual displayed price (sale_price / variation price).
                $frontendPrice = isset($item['price']) && (float) $item['price'] > 0
                    ? (float) $item['price']
                    : null;

                $itemPrice = $frontendPrice !== null
                    ? $frontendPrice
                    : $this->getItemPrice($product, $variant);

                // Apply dynamic bulk discount rules if any
                $cumulativeQty = $productQuantities[$product->id] ?? 0;
                $bulkDiscountPerItem = 0;
                if (!empty($product->bulk_discount_rules) && is_array($product->bulk_discount_rules)) {
                    foreach ($product->bulk_discount_rules as $rule) {
                        $minQty = isset($rule['min_qty']) ? (int) $rule['min_qty'] : 0;
                        $discAmt = isset($rule['discount_amount']) ? (float) $rule['discount_amount'] : 0;
                        if ($minQty > 0 && $cumulativeQty >= $minQty) {
                            $bulkDiscountPerItem = max($bulkDiscountPerItem, $discAmt);
                        }
                    }
                }

                if ($bulkDiscountPerItem > 0) {
                    $itemPrice = max(0, $itemPrice - $bulkDiscountPerItem);
                }

                $totalPrice += $itemPrice * $quantity;

                $orderItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'price' => $itemPrice,
                    'variation_snapshot' => $item['variation_snapshot'] ?? null,
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

                if (
                    CouponUsage::where('coupon_id', $coupon->id)
                        ->where('user_id', $user->id)
                        ->exists()
                ) {
                    DB::rollBack();
                    return response()->json(['detail' => 'You have already used this coupon.'], 400);
                }

                if (!$coupon->isValid()) {
                    DB::rollBack();
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
            'email' => $request->filled('email') ? $request->email : null,
            'subtotal' => $totalPrice + $discountAmount, // Before coupon
            'discount' => $discountAmount,
            'total_price' => $totalPrice + ($request->shipping_cost ?? 0), // You might want to add shipping cost here if passed from frontend
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
            'referred_by_id' => $referrer?->id,
            'referral_source' => $request->referral_source ?? ($referrer ? 'store_link' : null),
            'order_type' => ($user && $user->isAnyDropshipper()) ? 'dropshipping' : ($referrer ? 'referral' : 'direct'),
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
            $variationSnapshot = $item['variation_snapshot'];

            if (!$variationSnapshot && $item['variant']) {
                $variationSnapshot = trim(($item['variant']->size ? "Size: {$item['variant']->size}, " : "") . ($item['variant']->color ? "Color: {$item['variant']->color}" : ""), ", ");
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_variation_id' => $item['variant']?->id,
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'purchase_price' => $item['product']->purchase_price ?? 0,
                'total_price' => $item['price'] * $item['quantity'],
                'product_name' => $item['product']->name,
                'variation_snapshot' => $variationSnapshot,
            ]);

            if ($item['product']->api_from !== 'mohasagor') {
                // Deduct Product Stock
                if ($item['product']->current_stock !== null) {
                    $item['product']->decrement('current_stock', $item['quantity']);
                }
                if ($item['product']->stock_quantity !== null) {
                    $item['product']->decrement('stock_quantity', $item['quantity']);
                }

                // Deduct Variation Stock
                if ($item['variant'] && $item['variant']->stock_quantity !== null) {
                    $item['variant']->decrement('stock_quantity', $item['quantity']);
                }
            }
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

        // Mark checkout lead as converted if session_token is provided
        $sessionToken = $request->input('session_token');
        if ($sessionToken) {
            \App\Models\CheckoutLead::where('session_token', $sessionToken)
                ->where('converted', false)
                ->update([
                    'converted' => true,
                    'order_id' => $order->id,
                ]);
        }

            // Process Payment
            $paymentService = new \App\Services\PaymentService();
            $paymentResult = $paymentService->createPayment(
                $request->payment_method,
                $totalPrice + ($request->shipping_cost ?? 0),
                $request->name ?? ($user ? ($user->first_name . ' ' . $user->last_name) : 'Guest'),
                $request->filled('email') ? $request->email : ($user ? $user->email : ''),
                $contactNumber,
                $user
            );

            // Link transaction and payment IDs to the Order
            $merchantTxnId = $paymentResult['data']['merchant_transaction_id'] ?? null;
            $paymentObj = $paymentResult['data']['payment'] ?? null;

            if ($merchantTxnId) {
                $order->transaction_id = $merchantTxnId;
                \App\Models\EpsTransaction::where('merchant_transaction_id', $merchantTxnId)->update(['order_id' => $order->id]);
            }
            if ($paymentObj && isset($paymentObj->id)) {
                $order->payment_id = $paymentObj->id;
            }
            $order->save();

            $order->load(['items.product.images', 'items.variation.images']);

            DB::commit();

            return response()->json([
                'order' => new OrderResource($order),
                'payment_result' => $paymentResult
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => ['server' => [$e->getMessage()]]], 500);
        }
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
        if (
            Review::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists()
        ) {
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

    public function trackOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|string',
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $order = Order::where(function ($query) use ($request) {
            $query->where('order_number', $request->order_id)
                ->orWhere('id', $request->order_id);
        })
            ->where('contact_number', $request->phone_number)
            ->with(['items.product.images', 'items.variation.images'])
            ->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found with the provided details.'], 404);
        }

        return response()->json(new OrderResource($order));
    }
}
