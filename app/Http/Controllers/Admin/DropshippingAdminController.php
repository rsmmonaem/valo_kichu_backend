<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\User;
use App\Models\IpLog;
use App\Models\WalletTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Withdraw;
use Barryvdh\DomPDF\Facade\Pdf;
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
                'dropshipper_withdrawal_amount' => BusinessSetting::getValue('dropshipper_withdrawal_amount', 500),
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
            'dropshipper_withdrawal_amount' => 'required|numeric|min:0',
        ]);

        BusinessSetting::setValue('dropshipper_global_margin', $validated['global_margin']);
        BusinessSetting::setValue('sub_dropshipper_global_margin', $validated['sub_dropshipper_margin']);
        BusinessSetting::setValue('sub_sub_dropshipper_global_margin', $validated['sub_sub_dropshipper_margin']);
        BusinessSetting::setValue('dropshipper_withdrawal_amount', $validated['dropshipper_withdrawal_amount']);

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
            'is_approved' => true, // Admin created users are approved
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Dropshipper created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * List only pending dropshippers
     */
    public function listPendingDropshippers(Request $request)
    {
        $users = User::whereIn('role', ['dropshipper', 'sub_dropshipper', 'sub_sub_dropshipper'])
            ->where('is_approved', false)
            ->with(['parent'])
            ->paginate(20);

        return response()->json(['status' => 'success', 'data' => $users]);
    }

    /**
     * Approve a dropshipper
     */
    public function approveDropshipper(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        if (!$user->isAnyDropshipper()) {
            return response()->json(['error' => 'User is not a dropshipper.'], 400);
        }

        if ($user->is_approved) {
            return response()->json(['message' => 'User is already approved.']);
        }

        $user->is_approved = true;
        $user->is_active = true; // Ensure they are active too
        $user->save();

        // Send Email
        try {
            \Illuminate\Support\Facades\Mail::to($user->email)->send(new \App\Mail\DropshipperApprovedMail($user));
        } catch (\Exception $e) {
            \Log::error('Failed to send dropshipper approval email: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dropshipper approved successfully and email sent.'
        ]);
    }

    /**
     * Update a dropshipper
     */
    public function updateDropshipper(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'phone_number' => 'sometimes|string|unique:users,phone_number,' . $user->id,
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|in:dropshipper,sub_dropshipper,sub_sub_dropshipper',
            'parent_id' => 'nullable|exists:users,id',
            'margin' => 'nullable|numeric|min:0',
        ]);

        if ($request->has('password') && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->has('parent_id')) {
            $validated['refer_by'] = $validated['parent_id'];
            unset($validated['parent_id']);
        }

        if ($request->has('margin')) {
            $validated['dropshipper_margin'] = $validated['margin'];
            unset($validated['margin']);
        }

        $user->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Dropshipper updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete a dropshipper
     */
    public function deleteDropshipper($id)
    {
        $user = User::findOrFail($id);
        
        // Check if user has children or orders, handle accordingly?
        // For now, simple delete.
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Dropshipper deleted successfully'
        ]);
    }

    /**
     * Toggle active status (Ban/Unban)
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User status updated successfully',
            'is_active' => $user->is_active
        ]);
    }

    /**
     * Get user wallet details and transactions
     */
    public function getUserWallet($id)
    {
        $user = User::findOrFail($id);

        // Exact logic from DropshipperApiController::getStats()
        $orders = Order::where('user_id', $user->id)
            ->where('status', 'delivered')
            ->where('payment_method', 'cod')
            ->get();
        $orderitems = OrderItem::whereIn('order_id', $orders->pluck('id'))->get();

        $profit = 0;
        foreach ($orderitems as $item) {
            $product = Product::find($item->product_id);
            if ($product) {
                $price = $product->getCurrentPriceForUser($user);
                $PerProductProfit = $item->unit_price - $price;
                $profit += $PerProductProfit * $item->quantity;
            }
        }

        $totalWithdrawn = Withdraw::where('user_id', $user->id)
            ->whereIn('status', ['approved', 'pending'])
            ->sum('amount');

        $totalProfit = $profit - $totalWithdrawn;

        // Due Amount logic from getStats()
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

        // Other stats
        $activeOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();
        $subDropshippers = $user->children()->count();

        // Transactions (Unified list)
        $walletTransactions = WalletTransaction::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($t) {
                return [
                    'id' => $t->id,
                    'type' => $t->type, // credit/debit
                    'amount' => (float)$t->amount,
                    'description' => $t->description,
                    'created_at' => $t->created_at,
                    'source' => 'Wallet'
                ];
            });

        $withdrawals = Withdraw::where('user_id', $user->id)
            ->latest()
            ->get()
            ->map(function ($w) {
                return [
                    'id' => $w->id,
                    'type' => 'debit',
                    'amount' => (float)$w->amount,
                    'description' => 'Withdrawal Request',
                    'created_at' => $w->created_at,
                    'source' => 'Withdrawal',
                    'status' => $w->status
                ];
            });

        $transactions = $walletTransactions->concat($withdrawals)->sortByDesc('created_at')->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'refer_code' => $user->refer_code
                ],
                'stats' => [
                    'total_profit' => (float)$totalProfit,
                    'due_amount' => (float)$dueAmount,
                    'total_withdrawn' => (float)$totalWithdrawn,
                    'total_earned' => (float)$profit,
                    'active_orders' => $activeOrders,
                    'sub_dropshippers' => $subDropshippers
                ],
                'transactions' => $transactions
            ]
        ]);
    }

    /**
     * Generate PDF invoice for due amount
     */
    public function generateDueInvoice($id)
    {
        $user = User::findOrFail($id);

        $dueOrders = Order::where('user_id', $user->id)
            ->whereIn('status', ['delivered', 'Delivered', 'DELIVERED'])
            ->whereIn('payment_method', ['online', 'Online', 'ONLINE'])
            ->with('items')
            ->get();

        $dueDetails = [];
        $totalDue = 0;

        foreach ($dueOrders as $order) {
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $basePrice = $product->getCurrentPriceForUser($user);
                    $lineTotal = $basePrice * $item->quantity;
                    $dueDetails[] = [
                        'order_number' => $order->order_number,
                        'product_name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'base_price' => (float)$basePrice,
                        'line_total' => (float)$lineTotal,
                        'date' => $order->created_at->format('Y-m-d')
                    ];
                    $totalDue += $lineTotal;
                }
            }
        }

        if (count($dueDetails) === 0) {
            return response()->json(['message' => 'No due orders found'], 404);
        }

        $pdf = Pdf::loadView('pdf.due_invoice', [
            'user' => $user,
            'details' => $dueDetails,
            'total_due' => (float)$totalDue,
            'invoice_no' => 'DUE-' . strtoupper(Str::random(8)),
            'date' => now()->format('Y-m-d')
        ]);

        return $pdf->stream('due-invoice-' . $user->id . '.pdf');
    }

}

