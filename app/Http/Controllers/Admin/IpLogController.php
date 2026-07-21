<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IpLog;
use Illuminate\Http\Request;

class IpLogController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->input('limit', 20);
        $search = $request->input('search');

        $query = IpLog::query()->orderBy('updated_at', 'desc');

        if ($search) {
            $query->where('ip_address', 'like', "%{$search}%");
        }

        $logs = $query->paginate($limit);
        return response()->json($logs);
    }

    public function reset(Request $request, $id)
    {
        $log = IpLog::findOrFail($id);
        $log->request_count = 0;
        $log->is_banned = false;
        $log->ban_reason = null;
        $log->save();

        return response()->json(['message' => 'IP Limit has been reset successfully.']);
    }

    public function toggleUnlimited(Request $request, $id)
    {
        $log = IpLog::findOrFail($id);
        $log->is_unlimited = !$log->is_unlimited;
        
        // If we are making it unlimited, also unban it just in case
        if ($log->is_unlimited) {
            $log->is_banned = false;
            $log->ban_reason = null;
        }

        $log->save();

        return response()->json([
            'message' => 'IP Unlimited status updated.',
            'is_unlimited' => $log->is_unlimited
        ]);
    }

    public function delete($id)
    {
        $log = IpLog::findOrFail($id);
        $log->delete();

        return response()->json(['message' => 'IP Log deleted successfully.']);
    }
}
