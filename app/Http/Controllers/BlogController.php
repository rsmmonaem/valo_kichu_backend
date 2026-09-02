<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    // Public: get all blogs (with category)
    public function index(Request $request)
    {
        $query = Blog::with('category:id,name,slug')->where('status', 1);

        if ($request->has('category') && $request->category) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $blogs = $query->orderBy('id', 'desc')->get();
        return response()->json($blogs);
    }

    // Public: get featured blogs for carousel
    public function featured()
    {
        $blogs = Blog::with('category:id,name,slug')
            ->where('status', 1)
            ->where('is_featured', true)
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        return response()->json($blogs);
    }

    // Public: get single blog by slug and increment views
    public function show($slug)
    {
        $blog = Blog::with('category:id,name,slug')->where('slug', $slug)->firstOrFail();
        $blog->increment('views');
        return response()->json($blog);
    }

    // Public: get categories that have blogs
    public function blogCategories()
    {
        $categoryIds = Blog::where('status', 1)->whereNotNull('category_id')->pluck('category_id')->unique();
        $categories = \App\Models\Category::whereIn('id', $categoryIds)->select('id', 'name', 'slug')->get();
        return response()->json($categories);
    }

    // Admin: get all blogs (including inactive)
    public function adminIndex()
    {
        $blogs = Blog::with('category:id,name,slug')->orderBy('id', 'desc')->get();
        return response()->json($blogs);
    }

    // Admin: get single blog by ID
    public function adminShow($id)
    {
        $blog = Blog::with('category:id,name,slug')->findOrFail($id);
        return response()->json($blog);
    }

    // Admin: store blog
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|string',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        $slug = Str::slug($request->title);
        $count = Blog::where('slug', 'LIKE', "{$slug}%")->count();
        if ($count > 0) {
            $slug = $slug . '-' . ($count + 1);
        }

        $blog = Blog::create(array_merge($request->all(), ['slug' => $slug]));

        return response()->json(['message' => 'Blog created successfully', 'blog' => $blog->load('category:id,name,slug')]);
    }

    // Admin: update blog
    public function update(Request $request, $id)
    {
        $blog = Blog::findOrFail($id);
        
        $request->validate([
            'title' => 'required|string|max:255',
            'thumbnail' => 'nullable|string',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        if ($request->title !== $blog->title) {
            $slug = Str::slug($request->title);
            $count = Blog::where('slug', 'LIKE', "{$slug}%")->where('id', '!=', $id)->count();
            if ($count > 0) {
                $slug = $slug . '-' . ($count + 1);
            }
            $request->merge(['slug' => $slug]);
        }

        $blog->update($request->all());

        return response()->json(['message' => 'Blog updated successfully', 'blog' => $blog->load('category:id,name,slug')]);
    }

    // Admin: delete blog
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();
        return response()->json(['message' => 'Blog deleted successfully']);
    }
}
