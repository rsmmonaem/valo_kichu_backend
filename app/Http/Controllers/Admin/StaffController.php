<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    /**
     * List all staff members
     */
    public function index(Request $request)
    {
        $staffRoles = [
            'super_admin',
            'admin',
            'child_admin',
            'blogger',
            'content_writer',
            'blog_manager',
            'blog_editor',
            'order_manager',
            'product_manager',
            'staff',
            'custom'
        ];

        $users = User::whereIn('role', $staffRoles)
            ->orWhere('is_staff', true)
            ->latest()
            ->get();

        $mapped = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone_number,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'permissions' => is_array($user->permissions) ? $user->permissions : [],
                'is_active' => (bool)$user->is_active,
                'created_at' => $user->created_at ? $user->created_at->toIso8601String() : null,
            ];
        });

        return response()->json($mapped);
    }

    /**
     * Store a newly created staff member
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|unique:users,phone_number',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $nameParts = explode(' ', trim($request->name), 2);
        $firstName = $nameParts[0] ?? 'Staff';
        $lastName = $nameParts[1] ?? '';

        $email = $request->email ?: 'staff_' . Str::random(6) . '_' . time() . '@valokichu.com';

        // Set default permissions based on role if not provided
        $permissions = $request->permissions ?? [];
        if (empty($permissions)) {
            if ($request->role === 'blogger' || $request->role === 'content_writer') {
                $permissions = ['blogs'];
            } elseif ($request->role === 'order_manager') {
                $permissions = ['orders', 'reports'];
            } elseif ($request->role === 'product_manager') {
                $permissions = ['products', 'categories', 'brands', 'banners'];
            } elseif ($request->role === 'super_admin' || $request->role === 'admin') {
                $permissions = ['*'];
            }
        }

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $request->phone_number,
            'email' => $email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'permissions' => $permissions,
            'is_staff' => true,
            'is_active' => $request->has('is_active') ? (bool)$request->is_active : true,
            'is_approved' => true,
            'is_verified' => true,
            'refer_code' => Str::random(10),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Staff user created successfully',
            'user' => [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
                'phone' => $user->phone_number,
                'role' => $user->role,
                'permissions' => $user->permissions,
                'is_active' => (bool)$user->is_active,
            ]
        ], 201);
    }

    /**
     * Show single staff member
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return response()->json([
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone_number,
            'phone_number' => $user->phone_number,
            'role' => $user->role,
            'permissions' => is_array($user->permissions) ? $user->permissions : [],
            'is_active' => (bool)$user->is_active,
        ]);
    }

    /**
     * Update staff member
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20|unique:users,phone_number,' . $id,
            'email' => 'nullable|email|unique:users,email,' . $id,
            'password' => 'nullable|string|min:6',
            'role' => 'required|string',
            'permissions' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $nameParts = explode(' ', trim($request->name), 2);
        $user->first_name = $nameParts[0] ?? $user->first_name;
        $user->last_name = $nameParts[1] ?? '';
        $user->phone_number = $request->phone_number;
        if ($request->email) {
            $user->email = $request->email;
        }
        $user->role = $request->role;
        $user->is_staff = true;

        if ($request->has('permissions')) {
            $user->permissions = $request->permissions;
        }

        if ($request->has('is_active')) {
            $user->is_active = (bool)$request->is_active;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Staff user updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => trim($user->first_name . ' ' . $user->last_name),
                'email' => $user->email,
                'phone' => $user->phone_number,
                'role' => $user->role,
                'permissions' => $user->permissions,
                'is_active' => (bool)$user->is_active,
            ]
        ]);
    }

    /**
     * Delete staff member
     */
    public function destroy(Request $request, $id)
    {
        $currentUser = $request->user();
        if ($currentUser && $currentUser->id == $id) {
            return response()->json([
                'status' => false,
                'message' => 'You cannot delete your own account.'
            ], 400);
        }

        $user = User::findOrFail($id);
        
        // Safeguard: do not delete primary super admin
        if ($user->id === 1 || ($user->role === 'super_admin' && User::where('role', 'super_admin')->count() <= 1)) {
            return response()->json([
                'status' => false,
                'message' => 'The primary Super Admin account cannot be deleted.'
            ], 400);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'Staff member deleted successfully'
        ]);
    }

    /**
     * Toggle active status
     */
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
