<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Visitor;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $query = Visitor::withCount('pageViews');

        if ($request->has('filter')) {
            $filter = $request->filter;
            if ($filter === 'daily') {
                $query->whereDate('created_at', today());
            } elseif ($filter === 'monthly') {
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            }
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00', 
                $request->end_date . ' 23:59:59'
            ]);
        }

        $visitors = $query->orderBy('created_at', 'desc')->paginate(20);

        // Also fetch total unique visitors for the given period
        $stats = [
            'total_unique' => $visitors->total(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $visitors,
            'stats' => $stats
        ]);
    }

    public function show($id)
    {
        $visitor = Visitor::with(['pageViews' => function($q) {
            $q->orderBy('created_at', 'desc');
        }])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $visitor
        ]);
    }
}
