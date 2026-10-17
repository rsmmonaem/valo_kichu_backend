<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SearchSynonym;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SearchSynonymController extends Controller
{
    public function index(Request $request)
    {
        $query = SearchSynonym::query()->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('title', 'LIKE', "%{$search}%")
                  ->orWhere('terms', 'LIKE', "%{$search}%");
        }

        $perPage = $request->input('limit', 50);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'terms' => 'required|array|min:2',
            'terms.*' => 'string|max:255',
            'is_active' => 'boolean',
        ]);

        // Clean & lowercase all terms
        $cleanedTerms = array_values(array_unique(array_filter(array_map(function ($t) {
            return mb_strtolower(trim($t), 'UTF-8');
        }, $validated['terms']))));

        $synonym = SearchSynonym::create([
            'title' => $validated['title'],
            'terms' => $cleanedTerms,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        Cache::forget('search_synonyms_active_groups');

        return response()->json([
            'status' => true,
            'message' => 'Synonym group created successfully',
            'data' => $synonym
        ], 201);
    }

    public function show($id)
    {
        $synonym = SearchSynonym::findOrFail($id);
        return response()->json(['status' => true, 'data' => $synonym]);
    }

    public function update(Request $request, $id)
    {
        $synonym = SearchSynonym::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'terms' => 'sometimes|array|min:2',
            'terms.*' => 'string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if (isset($validated['terms'])) {
            $validated['terms'] = array_values(array_unique(array_filter(array_map(function ($t) {
                return mb_strtolower(trim($t), 'UTF-8');
            }, $validated['terms']))));
        }

        $synonym->update($validated);

        Cache::forget('search_synonyms_active_groups');

        return response()->json([
            'status' => true,
            'message' => 'Synonym group updated successfully',
            'data' => $synonym
        ]);
    }

    public function destroy($id)
    {
        $synonym = SearchSynonym::findOrFail($id);
        $synonym->delete();

        Cache::forget('search_synonyms_active_groups');

        return response()->json([
            'status' => true,
            'message' => 'Synonym group deleted successfully'
        ]);
    }
}
