<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckoutLead;
use Illuminate\Http\Request;

class CheckoutLeadAdminController extends Controller
{
    /**
     * List all checkout leads with optional filters.
     */
    public function index(Request $request)
    {
        $query = CheckoutLead::with('user:id,first_name,last_name,email')
            ->orderByDesc('updated_at');

        // Filter by conversion status
        if ($request->has('converted')) {
            $query->where('converted', filter_var($request->converted, FILTER_VALIDATE_BOOLEAN));
        }

        // Search by name or phone
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $leads = $query->paginate($request->input('per_page', 20));

        return response()->json($leads);
    }

    /**
     * Delete a single lead.
     */
    public function destroy($id)
    {
        CheckoutLead::findOrFail($id)->delete();
        return response()->json(['status' => true, 'message' => 'Lead deleted.']);
    }

    /**
     * Stats summary.
     */
    public function stats()
    {
        return response()->json([
            'total'     => CheckoutLead::count(),
            'converted' => CheckoutLead::where('converted', true)->count(),
            'pending'   => CheckoutLead::where('converted', false)->count(),
            'today'     => CheckoutLead::whereDate('created_at', today())->count(),
        ]);
    }
}
