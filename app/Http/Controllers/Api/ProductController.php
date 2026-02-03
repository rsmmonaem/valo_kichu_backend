<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        // Active products only
        $query->where('is_active', true);

        // Filter by Category
        if ($request->has('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        // Filter by Brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'price_low_high':
                    $query->orderBy('sale_price', 'asc');
                    break;
                case 'price_high_low':
                    $query->orderBy('sale_price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(20);

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['category', 'brand', 'variations', 'images', 'reviews'])
            ->first();

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }
}
