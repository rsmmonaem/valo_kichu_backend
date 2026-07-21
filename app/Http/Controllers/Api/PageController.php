<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show($type)
    {
        $page = Page::where('page_type', $type)->first();

        if (!$page) {
            return response()->json([
                'status' => 'error',
                'message' => 'Page not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $page
        ]);
    }
}
