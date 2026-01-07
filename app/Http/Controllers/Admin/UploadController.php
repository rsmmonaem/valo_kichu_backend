<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'folder' => 'required|string',
        ]);

        if ($request->hasFile('image')) {
            try {
                $file = $request->file('image');
                $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
                
                // Store file using 'public' disk
                $path = $file->storeAs($request->folder, $filename, 'public');
                
                Log::info('File uploaded', [
                    'filename' => $filename,
                    'path' => $path,
                    'disk' => 'public',
                    'exists' => Storage::disk('public')->exists($path)
                ]);
                
                // Generate full URL using Storage facade with 'public' disk
                $url = Storage::disk('public')->url($path);
                
                return response()->json([
                    'url' => $url,
                    'path' => $path,
                    'success' => true
                ]);
            } catch (\Exception $e) {
                Log::error('Upload failed', ['error' => $e->getMessage()]);
                return response()->json(['error' => 'Upload failed: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
