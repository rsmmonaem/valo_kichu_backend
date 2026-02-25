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
        $products = Product::with(['category', 'creator'])
            ->latest()
            ->paginate(40);

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Basic Info
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'specifications' => 'nullable|array',

            // Category / Brand
            'category_id' => 'required|exists:categories,id',
            'brand' => 'nullable|string',
            
            'category' =>'nullable|string',
            'api_id' => 'nullable|numeric|min:0',
            'product_code'=>'nullable|numeric|min:0',
            'api_from'=>'nullable|string',
            'slug'=>'nullable|string',

            // Product Info
            'product_type' => 'nullable|string',
            'product_sku' => 'nullable|string|unique:products,product_sku',
            'unit' => 'nullable|string',

            // Pricing
            'price' => 'required|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'unit_price' => 'nullable|numeric|min:0',

            // Stock
            'min_order_qty' => 'nullable|integer|min:1',
            'current_stock' => 'nullable|integer|min:0',

            // Discount
            'discount_type' => 'nullable|in:None,Flat,Percentage',
            'discount_amount' => 'nullable|numeric|min:0',

            // Tax
            'tax_amount' => 'nullable|numeric|min:0',
            'tax_calculation' => 'nullable|string',

            // Shipping
            'shipping_cost' => 'nullable|numeric|min:0',
            'shipping_multiply' => 'nullable|boolean',

            // Loyalty
            'loyalty_point' => 'nullable|numeric|min:0',
            // Image
            'image' => 'nullable|string',
            'gallery_images' => 'nullable|array',

            // JSON fields
            'variations' => 'nullable|array',
            'attributes' => 'nullable|array',
            'colors' => 'nullable|array',
            'tags' => 'nullable|array',

            // Status
            'status' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_trending' => 'boolean',
            'is_discounted' => 'boolean',

            // SEO
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($request, $validated) {

            $product = Product::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']) . '-' . Str::random(6),
                'description' => $validated['description'] ?? null,
                'specifications' => $validated['specifications'] ?? null,

                'category_id' => $validated['category_id'],
                'brand' => $validated['brand'] ?? null,

                'product_type' => $validated['product_type'] ?? null,
                'product_sku' => $validated['product_sku'] ?? null,
                'unit' => $validated['unit'] ?? null,

                // Prices
                'base_price' => $validated['price'],
                'purchase_price' => $validated['purchase_price'] ?? 0,
                'unit_price' => $validated['unit_price'] ?? 0,

                // Stock
                'min_order_qty' => $validated['min_order_qty'] ?? 1,
                'current_stock' => $validated['current_stock'] ?? 0,

                // Discount
                'discount_type' => $validated['discount_type'] ?? 'None',
                'discount_amount' => $validated['discount_amount'] ?? 0,

                // Tax
                'tax_amount' => $validated['tax_amount'] ?? 0,
                'tax_calculation' => $validated['tax_calculation'] ?? null,

                // Shipping
                'shipping_cost' => $validated['shipping_cost'] ?? 0,
                'shipping_multiply' => $validated['shipping_multiply'] ?? false,

                // Loyalty
                'loyalty_point' => $validated['loyalty_point'] ?? 0,
                // Image
                'image' => $validated['image'] ?? null,
                'gallery_images' =>$validated['gallery_images'] ?? [],

                // JSON
                'variations' => $request->variations ?? [],
                'attributes' => $validated['attributes'] ?? [],
                'colors' => $request->colors ?? [],
                'tags' => $request->tags ?? [],

                // Status
                'status' => $validated['status'] ?? 'active',
                'is_featured' => $validated['is_featured'] ?? false,
                'is_trending' => $validated['is_trending'] ?? false,
                'is_discounted' => $validated['is_discounted'] ?? false,

                // SEO
                'meta_title' => $validated['meta_title'] ?? null,
                'meta_description' => $validated['meta_description'] ?? null,
                'meta_keywords' => $validated['meta_keywords'] ?? null,

                'created_by_admin_id' => $request->user()->id,
            ]);

            return response()->json($product, 201);
        });
    }

    public function show(string $id)
    {
        return response()->json(
            Product::with(['category', 'creator'])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        $data = $request->except(['slug', 'created_by_admin_id']);

        if ($request->has('name')) {
            $data['slug'] = Str::slug($request->name) . '-' . Str::random(6);
        }

        $product->update($data);

        return response()->json($product);
    }

    public function destroy(string $id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted successfully'], 204);
    }
}
