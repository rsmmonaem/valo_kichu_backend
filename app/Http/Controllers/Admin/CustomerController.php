<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = User::where('role', 'customer')
            ->withCount('orders')
            ->latest()
            ->get();

        // Map fields to match what frontend expects
        $mappedCustomers = $customers->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
                'phone' => $user->phone_number,
                'orders_count' => $user->orders_count,
                'is_active' => (bool)$user->is_active,
                'image' => $user->image ? (str_starts_with($user->image, 'http') ? $user->image : asset('storage/users/' . $user->image)) : null,
                'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
            ];
        });

        return response()->json($mappedCustomers);
    }

    public function show($id)
    {
        $user = User::withCount('orders')->findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => trim($user->first_name . ' ' . $user->last_name) ?: 'Customer',
            'email' => $user->email,
            'phone' => $user->phone_number,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'orders_count' => $user->orders_count,
            'is_active' => (bool)$user->is_active,
            'image' => $user->image ? (str_starts_with($user->image, 'http') ? $user->image : asset('storage/users/' . $user->image)) : null,
            'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20|unique:users,phone_number,' . $user->id,
            'password' => 'nullable|string|min:6',
            'role' => 'nullable|string|in:customer,dropshipper,sub_dropshipper,sub_sub_dropshipper,admin',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'message' => $validator->errors()->first()], 422);
        }

        if ($request->has('first_name')) $user->first_name = $request->first_name;
        if ($request->has('last_name')) $user->last_name = $request->last_name;
        if ($request->has('email')) $user->email = strtolower(trim($request->email));
        if ($request->has('phone_number')) $user->phone_number = $request->phone_number;
        if ($request->has('is_active')) $user->is_active = (bool)$request->is_active;
        if ($request->has('role') && !empty($request->role)) $user->role = $request->role;
        if ($request->filled('password')) {
            $user->password = \Illuminate\Support\Facades\Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Customer updated successfully',
            'customer' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'name' => trim($user->first_name . ' ' . $user->last_name) ?: 'Customer',
                'email' => $user->email,
                'phone' => $user->phone_number,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_active' => (bool)$user->is_active,
            ]
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        \Illuminate\Support\Facades\DB::transaction(function () use ($user) {
            $userId = $user->id;

            // 1. Delete all orders and related items belonging to this user
            $orderIds = \App\Models\Order::withTrashed()->where('user_id', $userId)->pluck('id');
            if ($orderIds->isNotEmpty()) {
                \App\Models\OrderItem::whereIn('order_id', $orderIds)->delete();
                \App\Models\CheckoutLead::whereIn('order_id', $orderIds)->update(['order_id' => null]);
                \App\Models\EpsTransaction::whereIn('order_id', $orderIds)->update(['order_id' => null]);
                \App\Models\Order::withTrashed()->whereIn('id', $orderIds)->forceDelete();
            }

            // 2. Cascade delete Cart items
            \App\Models\CartItem::where('user_id', $userId)->delete();

            // 3. Cascade delete Addresses
            \App\Models\Address::where('user_id', $userId)->delete();

            // 4. Cascade delete OTPs
            \App\Models\Otp::where('user_id', $userId)->delete();

            // 5. Cascade delete Reviews & Favourites
            \App\Models\Review::where('user_id', $userId)->delete();
            \App\Models\FavouriteProduct::where('user_id', $userId)->delete();

            // 6. Cascade delete Notifications
            \App\Models\Notification::where('user_id', $userId)->delete();

            // 7. Cascade delete Dropshipper Profile & related
            \App\Models\DropShiper::where('customer_id', $userId)->delete();
            \App\Models\ApiKey::where('user_id', $userId)->delete();
            \App\Models\WalletTransaction::where('user_id', $userId)->delete();
            \App\Models\Wallet::where('user_id', $userId)->delete();
            \App\Models\Withdraw::where('user_id', $userId)->delete();

            // 8. Cascade delete Applied coupons
            \App\Models\AppliedCoupon::where('user_id', $userId)->delete();
            \App\Models\CouponUsage::where('user_id', $userId)->delete();

            // 9. Nullify references
            User::where('refer_by', $userId)->update(['refer_by' => null]);
            \App\Models\CheckoutLead::where('user_id', $userId)->update(['user_id' => null]);
            \App\Models\EpsTransaction::where('user_id', $userId)->update(['user_id' => null]);

            // 10. Delete Sanctum tokens
            $user->tokens()->delete();

            // 11. Finally delete the user
            $user->delete();
        });

        return response()->json([
            'status' => true,
            'message' => 'Customer and all associated orders/data deleted permanently.'
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = User::findOrFail($id);
        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully',
            'user' => $user
        ]);
    }
}
