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
            ->with(['children' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get()
            ->map(function ($category) {
                return $this->filterPopulatedCategory($category);
            })
            ->filter(function ($category) {
                return $category !== null;
            })
            ->values();

        return response()->json($categories);
    }

    private function filterPopulatedCategory($category)
    {
        if ($category->children->isNotEmpty()) {
            $filteredChildren = $category->children->map(function ($child) {
                return $this->filterPopulatedCategory($child);
            })->filter(function ($child) {
                return $child !== null;
            })->values();

            $category->setRelation('children', $filteredChildren);
        }

        $hasProducts = \App\Models\Product::where('category_id', $category->id)->where('is_active', true)->exists();
        $hasPopulatedChildren = $category->children->isNotEmpty();

        if (!$hasProducts && !$hasPopulatedChildren) {
            return null;
        }

        return $category;
    }

    public function show($slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        return response()->json($category);
    }
    public function categoryBars()
    {
        $categories = Category::where('is_active', true)->where('show_in_bar', true)->orderBy('priority','asc')->get();
            // dd($categories->toJson());

        return response()->json($categories);
    }
}
