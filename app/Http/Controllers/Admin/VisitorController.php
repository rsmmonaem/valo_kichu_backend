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

        // Search by IP, location, or FB tracking IDs
        if ($search = $request->input('search')) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('ip_address', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%")
                  ->orWhere('fb_event_id', 'like', "%{$search}%")
                  ->orWhere('fbc', 'like', "%{$search}%");
            });
        }

        if ($request->filled('filter')) {
            $filter = $request->filter;
            if ($filter === 'daily') {
                $query->where(function ($q) {
                    $q->whereDate('last_visited_at', today())
                      ->orWhereDate('created_at', today())
                      ->orWhereHas('pageViews', function ($pq) {
                          $pq->whereDate('created_at', today());
                      });
                });
            } elseif ($filter === 'monthly') {
                $query->where(function ($q) {
                    $q->where(function ($sub) {
                        $sub->whereMonth('last_visited_at', now()->month)
                            ->whereYear('last_visited_at', now()->year);
                    })->orWhere(function ($sub) {
                        $sub->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year);
                    })->orWhereHas('pageViews', function ($pq) {
                        $pq->whereMonth('created_at', now()->month)
                           ->whereYear('created_at', now()->year);
                    });
                });
            }
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate = $request->end_date . ' 23:59:59';
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('last_visited_at', [$startDate, $endDate])
                  ->orWhereBetween('created_at', [$startDate, $endDate])
                  ->orWhereHas('pageViews', function ($pq) use ($startDate, $endDate) {
                      $pq->whereBetween('created_at', [$startDate, $endDate]);
                  });
            });
        }

        $visitors = $query->orderByRaw('COALESCE(last_visited_at, updated_at, created_at) DESC')->paginate($request->input('per_page', 20));

        $totalUnique = Visitor::count();
        $todayUnique = Visitor::where(function ($q) {
            $q->whereDate('last_visited_at', today())
              ->orWhereDate('created_at', today())
              ->orWhereHas('pageViews', fn($pq) => $pq->whereDate('created_at', today()));
        })->count();
        $todayPageViews = \App\Models\VisitorPageView::whereDate('created_at', today())->count();
        $totalPageViews = \App\Models\VisitorPageView::count();

        $stats = [
            'total_unique'     => $totalUnique,
            'today_unique'     => $todayUnique,
            'today_page_views' => $todayPageViews,
            'total_page_views' => $totalPageViews,
            'filtered_total'   => $visitors->total(),
        ];

        return response()->json([
            'status' => 'success',
            'data'   => $visitors,
            'stats'  => $stats,
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
