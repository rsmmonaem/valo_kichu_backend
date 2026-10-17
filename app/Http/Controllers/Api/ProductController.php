<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\SearchService;
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
            $category = \App\Models\Category::where('slug', $request->category_slug)->first();
            if ($category) {
                $categoryIds = \App\Models\Category::getAllChildCategoryIds($category->id);
                $query->whereIn('category_id', $categoryIds);
            } else {
                // If category not found, return empty results
                $query->where('category_id', 0);
            }
        }

        // Filter by Brand
        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        // Filter by Price Range
        if ($request->has('min_price') && $request->min_price !== null && $request->min_price !== '') {
            $query->whereRaw('(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 THEN sale_price ELSE base_price END) >= ?', [$request->min_price]);
        }
        if ($request->has('max_price') && $request->max_price !== null && $request->max_price !== '') {
            $query->whereRaw('(CASE WHEN sale_price IS NOT NULL AND sale_price > 0 THEN sale_price ELSE base_price END) <= ?', [$request->max_price]);
        }

        // Search (Intelligent Bilingual Bengali + English + Banglish)
        if ($request->filled('search')) {
            $query = SearchService::applySearch($query, (string) $request->search);
        }

        // Sorting
        if ($request->has('sort_by')) {
            switch ($request->sort_by) {
                case 'low_to_high':
                case 'price_low_high':
                    $query->orderByRaw('COALESCE(sale_price, base_price) ASC');
                    break;
                case 'high_to_low':
                case 'price_high_low':
                    $query->orderByRaw('COALESCE(sale_price, base_price) DESC');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'oldest':
                    $query->orderBy('created_at', 'asc');
                    break;
                default:
                    // If searching and no specific sort provided, keep SearchService's relevance rank
                    if (!$request->filled('search')) {
                        $query->orderBy('created_at', 'desc');
                    }
            }
        } elseif (!$request->filled('search')) {
            $query->orderBy('created_at', 'desc');
        }

        $products = $query->paginate(40);

        return response()->json([
            'status' => true,
            'data' => $products
        ]);
    }

    /**
     * Provide instant search suggestions for autocomplete dropdown (Daraz-style).
     */
    public function suggestions(Request $request)
    {
        $query = $request->input('q') ?? $request->input('query') ?? $request->input('search') ?? '';
        $data = SearchService::getSuggestions((string) $query);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->with(['category', 'brand', 'images', 'reviews'])
            ->first();

        if (!$product && is_numeric($slug)) {
            $product = Product::where('id', $slug)
                ->with(['category', 'brand', 'images', 'reviews'])
                ->first();
        }

        if (!$product) {
            return response()->json([
                'status' => false,
                'message' => 'Product not found'
            ], 404);
        }

        $prev = Product::where('id', '<', $product->id)
            ->where('category_id', $product->category_id)
            ->where('status', 'active')
            ->orderBy('id', 'desc')
            ->first(['slug', 'image']);
            
        $next = Product::where('id', '>', $product->id)
            ->where('category_id', $product->category_id)
            ->where('status', 'active')
            ->orderBy('id', 'asc')
            ->first(['slug', 'image']);
        
        if (!$prev) {
            $prev = Product::where('category_id', $product->category_id)
                ->where('status', 'active')
                ->orderBy('id', 'desc')
                ->first(['slug', 'image']);
        }
        if (!$next) {
            $next = Product::where('category_id', $product->category_id)
                ->where('status', 'active')
                ->orderBy('id', 'asc')
                ->first(['slug', 'image']);
        }

        $product->setAttribute('prev_slug', $prev?->slug ?? null);
        $product->setAttribute('next_slug', $next?->slug ?? null);
        $product->setAttribute('prev_image', $prev?->image ?? null);
        $product->setAttribute('next_image', $next?->image ?? null);

        return response()->json([
            'status' => true,
            'data' => $product
        ]);
    }
}
