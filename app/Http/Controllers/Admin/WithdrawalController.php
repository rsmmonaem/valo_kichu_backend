<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdraw;
use Illuminate\Http\Request;

class WithdrawalController extends Controller
{
    public function index()
    {
        $withdrawals = Withdraw::with('user')->latest()->get();

        return response()->json([
            'data' => $withdrawals
        ]);
    }

    public function approve($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $withdraw->status = 'approved';
        $withdraw->save();

        return response()->json(['message' => 'Approved']);
    }

    public function reject($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $withdraw->status = 'rejected';
        $withdraw->save();

        return response()->json(['message' => 'Rejected']);
    }
}
