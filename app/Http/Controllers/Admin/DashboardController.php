<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalRevenue = Order::where('status', 'completed')->sum('total_amount');
        $totalOrders = Order::count();
        $totalCustomers = User::where('role', 'customer')->count();
        $totalProducts = Product::count();

        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        $trendingProducts = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderByDesc('total_sold')
            ->take(5)
            ->get()
            ->map(function ($item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $item->name = $product->name;
                    $item->image = $product->images[0] ?? null;
                }
                return $item;
            });

        return response()->json([
            'stats' => [
                'revenue' => $totalRevenue,
                'orders' => $totalOrders,
                'customers' => $totalCustomers,
                'products' => $totalProducts,
            ],
            'recent_orders' => $recentOrders,
            'trending_products' => $trendingProducts,
        ]);
    }
}
