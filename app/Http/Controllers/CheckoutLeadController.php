<?php

namespace App\Http\Controllers;

use App\Models\CheckoutLead;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutLeadController extends Controller
{
    /**
     * Save or update a checkout lead (partial form data).
     * Works for both logged-in users and guests.
     */
    public function save(Request $request)
    {
        $user = $request->user(); // null for guests

        $data = $request->only([
            'name', 'phone', 'email', 'address',
            'area', 'payment_method', 'notes',
            'cart_data',
        ]);

        // Remove null-ish empty values so we don't overwrite already saved data
        $data = array_filter($data, fn($v) => $v !== null && $v !== '');

        // Session token for guest tracking (sent from frontend, stored in localStorage)
        $sessionToken = $request->input('session_token');

        // Try to find existing lead
        $lead = null;

        if ($user) {
            // For logged-in users, find by user_id (not yet converted)
            $lead = CheckoutLead::where('user_id', $user->id)
                ->where('converted', false)
                ->latest()
                ->first();
        } elseif ($sessionToken) {
            // For guests, find by session token
            $lead = CheckoutLead::where('session_token', $sessionToken)
                ->where('converted', false)
                ->latest()
                ->first();
        }

        if ($lead) {
            $lead->update($data);
        } else {
            $lead = CheckoutLead::create(array_merge($data, [
                'user_id'       => $user?->id,
                'session_token' => $sessionToken ?? Str::uuid(),
                'converted'     => false,
            ]));
        }

        return response()->json([
            'status'        => true,
            'session_token' => $lead->session_token,
            'lead_id'       => $lead->id,
        ]);
    }

    /**
     * Mark a lead as converted (called when order is placed).
     */
    public function markConverted(string $sessionToken, int $orderId)
    {
        CheckoutLead::where('session_token', $sessionToken)
            ->where('converted', false)
            ->update([
                'converted' => true,
                'order_id'  => $orderId,
            ]);
    }
}
