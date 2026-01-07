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
}
