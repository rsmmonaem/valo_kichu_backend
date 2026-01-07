<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = \App\Models\Banner::where('is_active', true)
            ->orderBy('order_index', 'asc')
            ->get();
        return response()->json($banners);
    }

    public function flashBanners()
    {
        // For now, assuming flash banners are all active banners or a subset
        // You might want to add a 'type' column to the banners table later
        $banners = \App\Models\Banner::where('is_active', true)
            ->orderBy('order_index', 'asc')
            ->get();
        return response()->json($banners);
    }

    public function flashBannerDetail($id)
    {
        $banner = \App\Models\Banner::where('is_active', true)->findOrFail($id);
        return response()->json($banner);
    }

    public function banners()
    {
        $banners = \App\Models\Banner::where('is_active', true)
            ->orderBy('order_index', 'asc')
            ->get();
        return response()->json($banners);
    }

    public function bannerDetail($id)
    {
        $banner = \App\Models\Banner::where('is_active', true)->findOrFail($id);
        return response()->json($banner);
    }
}
