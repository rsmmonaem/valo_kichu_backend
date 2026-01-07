<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Banner::orderBy('order_index', 'asc')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'image_url' => 'required|url',
            'link' => 'nullable|string',
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'order_index' => 'integer',
            'is_active' => 'boolean'
        ]);

        $banner = Banner::create($validated);
        return response()->json($banner, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Banner $banner)
    {
        return response()->json($banner);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Banner $banner)
    {
        $validated = $request->validate([
            'image_url' => 'sometimes|url',
            'link' => 'nullable|string',
            'title' => 'nullable|string',
            'subtitle' => 'nullable|string',
            'order_index' => 'integer',
            'is_active' => 'boolean'
        ]);

        $banner->update($validated);
        return response()->json($banner);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banner $banner)
    {
        $banner->delete();
        return response()->json(null, 204);
    }
}
