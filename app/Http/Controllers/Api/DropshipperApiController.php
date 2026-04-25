<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\WalletTransaction;
use App\Models\Withdraw;
use App\Models\BusinessSetting;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DropshipperApiController extends Controller
{
    /**
     * Generate a new API key for the dropshipper
     */
    public function generateKey(Request $request)
    {
        $user = auth()->user();

        if (!$user->isAnyDropshipper()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $request->name ?? 'Default Key',
            'key' => (string) Str::uuid(),
            'secret' => Str::random(40),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $apiKey
        ], 201);
    }

    /**
     * List API keys
     */
    public function index()
    {
        $keys = auth()->user()->apiKeys;
        return response()->json(['data' => $keys]);
    }

    /**
     * Toggle status or delete key
     */
    public function update(Request $request, $id)
    {
        $key = auth()->user()->apiKeys()->findOrFail($id);
        $key->update($request->only('is_active', 'name', 'settings'));

        return response()->json(['message' => 'API Key updated', 'data' => $key]);
    }

    public function destroy($id)
    {
        $key = auth()->user()->apiKeys()->findOrFail($id);
        $key->delete();

        return response()->json(['message' => 'API Key deleted']);
    }

    /**
     * Get dashboard stats for the dropshipper
     */
    public function getStats()
    {
        $user = auth()->user();

        // $totalProfit = WalletTransaction::where('user_id', $user->id)
        //     ->where('type', 'credit')
        //     ->sum('amount');
        // $totalProfit = Order::where('user_id', $user->id)
        //     ->where('status', 'delivered')
        //     ->sum('subtotal');
        // calculate for cod orders only
        $orders = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('payment_method', 'cod')
            ->get();
        $orderitems = OrderItem::whereIn('order_id', $orders->pluck('id'))->get();

        $price = 0;
        $profit = 0;
        foreach ($orderitems as $item) {
            $product = Product::find($item->product_id);

            if ($product) {

                $price = $product->getCurrentPriceForUser($user);
                $PerProductProfit = $item->unit_price - $price;
                $profit += $PerProductProfit * $item->quantity;
            }
        }

        // withdrawal amount calculation can be done by summing all approved withdrawals for the user
        $totalWithdrawn = Withdraw::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount');


        $totalProfit = $profit - $totalWithdrawn;

        // calculate online payment due amount
        $orderDue = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('payment_method', 'online')
            ->get();
        $orderitemsDue = OrderItem::whereIn('order_id', $orderDue->pluck('id'))->get();
        $dueAmount = 0;
        foreach ($orderitemsDue as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $price = $product->getCurrentPriceForUser($user);
                $dueAmount += $price * $item->quantity;
            }
        }

        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $subDropshippers = $user->children()->count();

        // API usage is a bit tricky without user_id in IpLog, 
        // we can count total keys or just use a placeholder for now if it's not critical.
        // Let's use total hits from their active keys if possible, but IpLog is simpler for now.
        $apiUsage = ApiKey::where('user_id', $user->id)->count() . " keys";
        $minimumWithdrawalAmount = BusinessSetting::getValue('dropshipper_withdrawal_amount', 500);
        return response()->json([
            'status' => 'success',
            'data' => [
                'total_profit' => $totalProfit,
                'due_amount' => $dueAmount,
                'minimum_withdrawal_amount' => $minimumWithdrawalAmount,
                'active_orders' => $activeOrders,
                'sub_dropshippers' => $subDropshippers,
                'api_usage' => $apiUsage,
                'currency' => 'BDT' // Or from settings
            ]
        ]);
    }
    // withdrawal history can be implemented by creating a method that retrieves all withdrawal records for the authenticated user, filtering by status if needed, and returning them in a paginated format. Each record can include details like amount, status, date, and transaction ID.
    public function getWithdrawalHistory(Request $request)
    {
        $user = auth()->user();
        // $withdrawals = Withdraw::where('user_id', $user->id)
        //     ->latest()
        //     ->paginate(20);

        $withdrawals = Withdraw::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'debit', // 🔥 important
                    'amount' => $item->amount,
                    'status' => $item->status,
                    'description' => 'Withdrawal Request',
                    'created_at' => $item->created_at,
                    'transaction_id' => $item->transaction_id,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $withdrawals
        ]);
    }

    // withdrawal requests can be implemented similarly by creating a WithdrawalRequest model and controller methods to handle them. For now, we will skip that part as it was not included in the original code.
    public function getWithdrawalRequests(Request $request)
    {
        $user = auth()->user();
        $amount = $request->input('amount');
        $withdraw = Withdraw::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        if (!$withdraw) {
            return response()->json(['message' => 'Failed to create withdrawal request'], 500);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'data' => $withdraw
            ]
        ]);
    }


    /**
     * Get orders for the dropshipper
     */
    public function getOrders()
    {
        $user = auth()->user();
        $orders = Order::where('user_id', $user->id)
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * Get referred children (sub-dropshippers)
     */
    public function getChildren()
    {
        $user = auth()->user();
        $children = $user->children;

        return response()->json([
            'status' => 'success',
            'data' => UserResource::collection($children)
        ]);
    }

    /**
     * Get wallet transaction history
     */
    public function getWallet()
    {
        $user = auth()->user();
        // calculate balance by summing all delivered orders
        // $dropShipperBalance = Order::where('user_id', $user->id)->where('payment_method','cod')->where('order_type','dropshipping')->sum('amount');
        // $orders= Order::where('user_id', $user->id)->where('payment_method','cod')->where('order_type','dropshipping')->get();
        // dd($orders);
        // die();
        $transactions = WalletTransaction::where('user_id', $user->id)
            ->latest()
            ->paginate(20);

        return response()->json($transactions);
    }

    /**
     * Get profile of the dropshipper
     */
    public function getProfile()
    {
        $user = auth()->user();
        $user->load('dropshipperProfile');

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => new UserResource($user),
                'store_name' => $user->dropshipperProfile?->name ?? ($user->first_name . ' ' . $user->last_name),
                'slogan' => $user->dropshipperProfile?->slogan,
                'about_us' => $user->dropshipperProfile?->about_us,
                'store_logo' => $user->dropshipperProfile?->store_logo,
                'store_banner' => $user->dropshipperProfile?->store_banner,
                'store_logo_url' => $user->dropshipperProfile?->store_logo_url,
                'store_banner_url' => $user->dropshipperProfile?->store_banner_url,
            ]
        ]);
    }

    /**
     * Update profile of the dropshipper
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|unique:users,phone_number,' . $user->id,
            'store_name' => 'sometimes|string|max:255',
            'store_logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'store_banner' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'slogan' => 'sometimes|string|max:255',
            'about_us' => 'sometimes|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $userData = $request->only('first_name', 'last_name', 'email', 'phone_number');

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('storage/users'), $imageName);
            $userData['image'] = $imageName;
        }


        // dd($userData);
        // die();
        $user->update($userData);

        if ($request->hasAny(['store_name', 'slogan', 'about_us', 'store_logo', 'store_banner'])) {
            $profileData = [];
            if ($request->has('store_name')) $profileData['name'] = $request->store_name;
            // if ($request->has('slogan')) $profileData['slogan'] = $request->slogan;
            if ($request->has('slogan')) {
                $profileData['slogan'] = $request->input('slogan', '');
            }
            if ($request->has('about_us')) $profileData['about_us'] = $request->about_us;

            // Handle logo
            if ($request->hasFile('store_logo')) {
                $logoName = 'logo_' . time() . '.' . $request->store_logo->extension();
                $request->store_logo->move(public_path('storage/stores'), $logoName);
                $profileData['store_logo'] = $logoName;
            }

            // Handle banner
            if ($request->hasFile('store_banner')) {
                $bannerName = 'banner_' . time() . '.' . $request->store_banner->extension();
                $request->store_banner->move(public_path('storage/stores'), $bannerName);
                $profileData['store_banner'] = $bannerName;
            }

            $profileData = [
                'name' => $request->input('store_name', ''),
                'slogan' => $request->input('slogan', ''),
                'about_us' => $request->input('about_us', ''),
            ];

            $user->dropshipperProfile()->updateOrCreate(
                ['customer_id' => $user->id],
                array_merge($profileData, ['email' => $user->email, 'phone' => $user->phone_number])
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => [
                'user' => new UserResource($user->fresh(['dropshipperProfile'])),
                'store_name' => $user->dropshipperProfile?->name,
                'slogan' => $user->dropshipperProfile?->slogan,
                'about_us' => $user->dropshipperProfile?->about_us,
                'store_logo' => $user->dropshipperProfile?->store_logo,
                'store_banner' => $user->dropshipperProfile?->store_banner,
                'store_logo_url' => $user->dropshipperProfile?->store_logo_url,
                'store_banner_url' => $user->dropshipperProfile?->store_banner_url,
            ]
        ]);
    }

    /**
     * Get public store info by referral code (username)
     */
    public function getPublicStore($refer_code)
    {
        $user = User::where('refer_code', $refer_code)->firstOrFail();
        $user->load('dropshipperProfile');

        return response()->json([
            'status' => 'success',
            'data' => [
                'name' => $user->dropshipperProfile?->name ?? ($user->first_name . ' ' . $user->last_name),
                'logo' => $user->dropshipperProfile?->store_logo ? asset('storage/stores/' . $user->dropshipperProfile->store_logo) : null,
                'banner' => $user->dropshipperProfile?->store_banner ? asset('storage/stores/' . $user->dropshipperProfile->store_banner) : null,
                'slogan' => $user->dropshipperProfile?->slogan,
                'about' => $user->dropshipperProfile?->about_us,
                'social' => $user->dropshipperProfile?->social_links,
            ]
        ]);
    }

    public function getTrackingInfo(Request $request)
    {


        $trackingNumber = $request->get('tracking_number');
        if (!$trackingNumber) {
            return response()->json(['message' => 'Tracking number is required'], 400);
        }

        $orders = Order::where('order_number', $trackingNumber)->first();
        if (!$orders) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        $order_items = OrderItem::where('order_id', $orders->id)->first();

        // For demonstration, we'll return dummy tracking info.
        // In a real implementation, you'd integrate with a shipping API.
        $trackingInfo = [
            'tracking_number' => $trackingNumber,
            'status' => $orders->status,
            'product_name' => $order_items->product_name,

        ];

        return response()->json([
            'status' => 'success',
            'data' => $trackingInfo
        ]);
    }
    public function cancelOrder(Request $request)
    {
        $orderNumber = $request->get('order_number');
        if (!$orderNumber) {
            return response()->json(['message' => 'Order number is required'], 400);
        }

        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Check if the order can be cancelled (e.g., only if it's pending)
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be cancelled'], 400);
        }

        $order->status = 'cancelled';
        $order->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully'
        ]);
    }
}
