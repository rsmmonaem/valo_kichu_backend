<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageAdminController extends Controller
{
    public function show($type)
    {
        $page = Page::firstOrCreate(
            ['page_type' => $type],
            ['title' => ucfirst(str_replace('_', ' ', $type)), 'content' => '', 'status' => true]
        );

        return response()->json([
            'status' => 'success',
            'data' => $page
        ]);
    }

    public function update(Request $request, $type)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'status' => 'boolean'
        ]);

        $page = Page::firstOrCreate(['page_type' => $type]);
        
        $page->update([
            'title' => $request->title,
            'content' => $request->content,
            'status' => $request->status ?? true
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Page updated successfully',
            'data' => $page
        ]);
    }
}
