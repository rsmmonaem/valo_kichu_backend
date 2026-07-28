<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SourcePage;
use Illuminate\Http\Request;

class SourcePageController extends Controller
{
    /**
     * Display a listing of the source pages.
     */
    public function index()
    {
        $pages = SourcePage::where('is_active', true)->orderBy('id', 'desc')->get();
        return response()->json([
            'status' => 'success',
            'data' => $pages,
        ]);
    }

    /**
     * Store a newly created source page with optional logo upload.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string',
        ]);

        $page = SourcePage::create([
            'name' => trim($request->name),
            'logo' => $request->logo,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Source page created successfully',
            'data' => $page,
        ]);
    }

    /**
     * Remove the specified source page.
     */
    public function destroy($id)
    {
        $page = SourcePage::findOrFail($id);
        $page->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Source page deleted successfully',
        ]);
    }
}
