<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => function($q) {
                $q->where('is_active', true);
            }])
            ->get();
            
        return response()->json($categories);
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return response()->json($category);
    }
}
