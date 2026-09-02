<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FeedController extends Controller
{
    /**
     * List all feeds.
     */
    public function index()
    {
        $feeds = Feed::orderBy('created_at', 'desc')->get();
        return response()->json(['status' => 'success', 'data' => $feeds]);
    }

    /**
     * Store a new feed configuration.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:feeds,name',
            'format' => 'required|in:facebook_csv',
            'field_mapping' => 'nullable|array',
            'schedule_cron' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
        $feed = Feed::create($validator->validated());
        return response()->json(['status' => 'success', 'data' => $feed]);
    }

    /**
     * Show a single feed.
     */
    public function show($id)
    {
        $feed = Feed::findOrFail($id);
        return response()->json(['status' => 'success', 'data' => $feed]);
    }

    /**
     * Update an existing feed.
     */
    public function update(Request $request, $id)
    {
        $feed = Feed::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'name' => "sometimes|string|unique:feeds,name,{$feed->id}",
            'format' => 'sometimes|in:facebook_csv',
            'field_mapping' => 'nullable|array',
            'schedule_cron' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }
        $feed->update($validator->validated());
        return response()->json(['status' => 'success', 'data' => $feed]);
    }

    /**
     * Delete a feed.
     */
    public function destroy($id)
    {
        $feed = Feed::findOrFail($id);
        $feed->delete();
        return response()->json(['status' => 'success', 'message' => 'Feed deleted']);
    }

    /**
     * Trigger on‑demand generation of a feed.
     */
    public function generate($id)
    {
        $feed = Feed::findOrFail($id);
        $service = app(\App\Services\FeedGeneratorService::class);
        $url = $service->generate($feed);
        return response()->json(['status' => 'success', 'feed_url' => $url]);
    }
}
?>
