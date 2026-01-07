<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\FlashBanner;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function flashBanners()
    {
        $banners = Banner::where('published', 1)->where('banner_type', 'Flash Banner')->with(['product', 'category', 'brand'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'banner_type' => $banner->banner_type,
                    'url' => $banner->url,
                    'published' => $banner->published,
                    'image' => $banner->image_url, // Use accessor for full URL
                    'product_id' => $banner->product_id,
                    'category_id' => $banner->category_id,
                    'brand_id' => $banner->brand_id,
                    'product' => $banner->product,
                    'category' => $banner->category,
                    'brand' => $banner->brand,
                    'created_at' => $banner->created_at,
                    'updated_at' => $banner->updated_at,
                ];
            });

        return response()->json($banners);
    }

    public function flashBannerDetail($id)
    {
        $banner = Banner::where('published', 1)->where('banner_type', 'Flash Banner')->with(['product', 'category', 'brand'])
            ->findOrFail($id);

        return response()->json([
            'id' => $banner->id,
            'title' => $banner->title,
            'banner_type' => $banner->banner_type,
            'url' => $banner->url,
            'published' => $banner->published,
            'image' => $banner->image_url, // Use accessor for full URL
            'product_id' => $banner->product_id,
            'category_id' => $banner->category_id,
            'brand_id' => $banner->brand_id,
            'product' => $banner->product,
            'category' => $banner->category,
            'brand' => $banner->brand,
            'created_at' => $banner->created_at,
            'updated_at' => $banner->updated_at,
        ]);
    }

    public function banners()
    {
        $banners = Banner::where('published', 1)->where('banner_type', 'Main Banner')->with(['product', 'category', 'brand'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($banner) {
                return [
                    'id' => $banner->id,
                    'title' => $banner->title,
                    'banner_type' => $banner->banner_type,
                    'url' => $banner->url,
                    'published' => $banner->published,
                    'image' => $banner->image_url, // Use accessor for full URL
                    'product_id' => $banner->product_id,
                    'category_id' => $banner->category_id,
                    'brand_id' => $banner->brand_id,
                    'product' => $banner->product,
                    'category' => $banner->category,
                    'brand' => $banner->brand,
                    'created_at' => $banner->created_at,
                    'updated_at' => $banner->updated_at,
                ];
            });

        return response()->json($banners);
    }

    public function bannerDetail($id)
    {
        $banner = Banner::where('published', 1)->where('banner_type', 'Main Banner')->with(['product', 'category', 'brand'])
            ->findOrFail($id);

        return response()->json([
            'id' => $banner->id,
            'title' => $banner->title,
            'banner_type' => $banner->banner_type,
            'url' => $banner->url,
            'published' => $banner->published,
            'image' => $banner->image_url, // Use accessor for full URL
            'product_id' => $banner->product_id,
            'category_id' => $banner->category_id,
            'brand_id' => $banner->brand_id,
            'product' => $banner->product,
            'category' => $banner->category,
            'brand' => $banner->brand,
            'created_at' => $banner->created_at,
            'updated_at' => $banner->updated_at,
        ]);
    }
}
