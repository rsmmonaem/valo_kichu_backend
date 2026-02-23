<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\IpLog;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DropshippingAdminController extends Controller
{
    /**
     * Get global dropshipping settings
     */
    public function getSettings()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'global_margin' => BusinessSetting::getValue('dropshipper_global_margin', 70),
                'sub_dropshipper_margin' => BusinessSetting::getValue('sub_dropshipper_global_margin', 60),
                'sub_sub_dropshipper_margin' => BusinessSetting::getValue('sub_sub_dropshipper_global_margin', 50),
            ]
        ]);
    }

    /**
     * Update global dropshipping settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'global_margin' => 'required|numeric|min:0',
            'sub_dropshipper_margin' => 'required|numeric|min:0',
            'sub_sub_dropshipper_margin' => 'required|numeric|min:0',
        ]);

        BusinessSetting::setValue('dropshipper_global_margin', $validated['global_margin']);
        BusinessSetting::setValue('sub_dropshipper_global_margin', $validated['sub_dropshipper_margin']);
        BusinessSetting::setValue('sub_sub_dropshipper_global_margin', $validated['sub_sub_dropshipper_margin']);

        return response()->json(['message' => 'Settings updated successfully']);
    }

    /**
     * List all dropshippers with their levels
     */
    public function listDropshippers(Request $request)
    {
        $users = User::whereIn('role', ['dropshipper', 'sub_dropshipper', 'sub_sub_dropshipper'])
            ->with(['parent', 'children'])
            ->paginate(20);

        return response()->json(['status' => 'success', 'data' => $users]);
    }

    /**
     * Manage banned IPs
     */
    public function listBannedIps()
    {
        $ips = IpLog::where('is_banned', true)->get();
        return response()->json(['status' => 'success', 'data' => $ips]);
    }

    public function toggleIpBan(Request $request, $id)
    {
        $ip = IpLog::findOrFail($id);
        $ip->is_banned = !$ip->is_banned;
        $ip->ban_reason = $request->reason ?? ($ip->is_banned ? 'Manual ban' : null);
        $ip->save();

        return response()->json(['message' => 'IP status updated']);
    }

    /**
     * Store a newly created dropshipper (Admin action)
     */
    public function storeDropshipper(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:8',
            'role' => 'required|in:dropshipper,sub_dropshipper,sub_sub_dropshipper',
            'parent_id' => 'nullable|exists:users,id',
            'margin' => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'refer_by' => $validated['parent_id'],
            'dropshipper_margin' => $validated['margin'] ?? 0,
            'refer_code' => Str::random(10),
            'is_active' => true,
            'is_verified' => true, // Admin created users are verified
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dropshipper created successfully',
            'data' => $user
        ], 201);
    }
}
