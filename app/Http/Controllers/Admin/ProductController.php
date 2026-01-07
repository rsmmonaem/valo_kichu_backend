<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'variations'])->latest()->paginate(20);
        return response()->json($products);
    }

    public function store(Request $request)
    {
        // Simple validation for API/Admin products
        $validated = $request->validate([
            'name' => 'required|string',
            'category_id' => 'nullable|exists:categories,id',
            'source_type' => 'in:api,admin',
            'base_price' => 'numeric',
            'variations' => 'array',
        ]);

        $data = $request->except('variations');
        $data['slug'] = Str::slug($data['name']) . '-' . Str::random(6);
        $data['created_by_admin_id'] = $request->user()->id;

        return DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);

            if ($request->has('variations')) {
                foreach ($request->variations as $var) {
                    $product->variations()->create($var);
                }
            }
            
            return response()->json($product->load('variations'), 201);
        });
    }

    public function show(string $id)
    {
        return Product::with(['variations', 'category', 'creator'])->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->except('variations'));
        
        // Basic variation handling: if variations provided, replace all? 
        // Or update specific ones? For MVP, let's assume UI handles logic or separate endpoint.
        
        return response()->json($product);
    }

    public function destroy(string $id)
    {
        Product::destroy($id);
        return response()->json(null, 204);
    }
}
