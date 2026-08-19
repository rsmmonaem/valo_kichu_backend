<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('is_active', true)
            ->with(['category']);
        
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('category_slug')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        if ($request->has('search') && !empty(trim($request->search))) {
            $search = trim($request->search);
            $cleanSearch = preg_replace('/[^a-zA-Z0-9 ]/', '', $search);
            if (!empty($cleanSearch)) {
                $query->where('name', 'REGEXP', '(^|[^a-zA-Z0-9])' . $cleanSearch . '($|[^a-zA-Z0-9])');
            }
        }

        // return response()->json($query->paginate(40));
        return response()->json($query->paginate(40));

    }

    public function show(string $id)
    {
        return Product::with(['category', 'variations'])->findOrFail($id);
    }
}
