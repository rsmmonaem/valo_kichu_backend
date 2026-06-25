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

    public function toggleStatus(Request $request, $id)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $user = User::where('role', 'customer')->findOrFail($id);
        $user->is_active = $request->is_active;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully',
            'user' => $user
        ]);
    }
}
