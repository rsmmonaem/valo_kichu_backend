<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function courierReport(Request $request)
    {
        $query = Order::query();

        // Date Range Filtering
        if ($request->has('start_date') && !empty($request->start_date)) {
            $start = Carbon::parse($request->start_date, 'Asia/Dhaka')->startOfDay()->setTimezone('UTC');
            $query->where('created_at', '>=', $start);
        }

        if ($request->has('end_date') && !empty($request->end_date)) {
            $end = Carbon::parse($request->end_date, 'Asia/Dhaka')->endOfDay()->setTimezone('UTC');
            $query->where('created_at', '<=', $end);
        }

        if ($request->has('page_name') && !empty($request->page_name)) {
            $query->where('page_name', $request->page_name);
        }

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('courier_name') && !empty($request->courier_name)) {
            if ($request->courier_name === 'unassigned') {
                $query->whereNull('courier_name');
            } else {
                $query->where('courier_name', $request->courier_name);
            }
        }

        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('order_number', 'like', "%{$searchTerm}%")
                  ->orWhere('id', 'like', "%{$searchTerm}%")
                  ->orWhere('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('contact_number', 'like', "%{$searchTerm}%");
            });
        }

        // Get total summary
        $totalOrders = (clone $query)->count();
        $totalRevenue = (clone $query)->whereNotIn('status', ['pending', 'cancelled', 'refunded', 'returned'])->sum('total_price');

        // Group by courier_name and get counts and revenues
        $courierData = (clone $query)
            ->select(
                DB::raw('COALESCE(courier_name, "Unassigned") as courier'),
                DB::raw('count(*) as count'),
                DB::raw('sum(case when status not in ("pending", "cancelled", "refunded", "returned") then total_price else 0 end) as revenue')
            )
            ->groupBy('courier')
            ->get();

        // Get status counts grouped by courier
        $statusBreakdown = (clone $query)
            ->select(
                DB::raw('COALESCE(courier_name, "Unassigned") as courier'),
                'status',
                DB::raw('count(*) as count')
            )
            ->groupBy('courier', 'status')
            ->get()
            ->groupBy('courier');

        $couriers = [];
        foreach ($courierData as $data) {
            $name = $data->courier;
            $statuses = [];
            
            // Initialize common statuses to 0
            $commonStatuses = ['pending', 'confirmed', 'shipping', 'delivered', 'cancelled', 'refunded', 'transfer_to_courier', 'returned'];
            foreach ($commonStatuses as $st) {
                $statuses[$st] = 0;
            }

            if (isset($statusBreakdown[$name])) {
                foreach ($statusBreakdown[$name] as $breakdown) {
                    $statuses[$breakdown->status] = $breakdown->count;
                }
            }

            // Also calculate total completed/delivered orders vs cancelled
            $deliveredCount = $statuses['delivered'] ?? 0;
            $cancelledCount = $statuses['cancelled'] ?? 0;
            $totalForCourier = $data->count;
            $successRate = $totalForCourier > 0 ? round(($deliveredCount / $totalForCourier) * 100, 2) : 0;

            $couriers[] = [
                'courier_name' => $name,
                'total_orders' => $totalForCourier,
                'total_revenue' => (float)$data->revenue,
                'success_rate' => $successRate,
                'statuses' => $statuses
            ];
        }

        return response()->json([
            'summary' => [
                'total_orders' => $totalOrders,
                'total_revenue' => (float)$totalRevenue,
            ],
            'couriers' => $couriers
        ]);
    }
}
