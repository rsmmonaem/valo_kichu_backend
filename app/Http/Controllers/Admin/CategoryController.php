<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('children.children')->whereNull('parent_id')->latest()->get();
        return response()->json($categories);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string', // Assuming URL or path
            'is_active' => 'boolean',
            'priority' => 'nullable|integer|min:1|max:10',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'show_in_bar' => 'boolean',
            'bar_icon' => 'nullable|string',
            'custom_icon' => 'nullable|string',
        ]);
       
        $slug = Str::slug($validated['name']);
        // Ensure slug uniqueness logic if needed, but basic slug here:
        $count = Category::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            $slug .= '-' . ($count + 1);
        }

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'parent_id' => $validated['parent_id'] ?? null,
            'image' => $validated['image'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'priority' => $validated['priority'] ?? null,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
            'meta_keywords' => $validated['meta_keywords'] ?? null,
            'show_in_bar' => $validated['show_in_bar'] ?? false,
            'bar_icon' => $validated['bar_icon'] ?? null,
            'custom_icon' => $validated['custom_icon'] ?? null,
        ]);

        return response()->json($category, 201);
    }

    public function show(string $id)
    {
        return Category::with('children')->findOrFail($id);
    }

    public function update(Request $request, string $id)
    {
        // dd($request->all());
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'image' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'priority' => 'nullable|integer|min:1|max:10',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string|max:255',
            'show_in_bar' => 'sometimes|boolean',
            'bar_icon' => 'nullable|string',
            'custom_icon' => 'nullable|string',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // Uniqueness check omitted for brevity but recommended
        }

        $category->update($validated);

        return response()->json($category);
    }

    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();
        return response()->json(null, 204);
    }
}
