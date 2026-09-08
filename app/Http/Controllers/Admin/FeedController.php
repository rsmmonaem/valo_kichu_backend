<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feed;
use App\Services\FeedGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeedController extends Controller
{
    protected FeedGeneratorService $feedService;

    public function __construct(FeedGeneratorService $feedService)
    {
        $this->feedService = $feedService;
    }

    /**
     * List all feeds.
     */
    public function index()
    {
        $feeds = Feed::orderBy('created_at', 'desc')->get()->map(function ($feed) {
            $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($feed->name));
            $filePath = "feeds/{$safeName}.csv";
            $feed->file_exists = Storage::disk('public')->exists($filePath);
            $feed->feed_url = $feed->file_exists ? Storage::disk('public')->url($filePath) : null;
            return $feed;
        });

        return response()->json(['status' => 'success', 'data' => $feeds]);
    }

    /**
     * Store a new feed configuration and generate its CSV immediately.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:feeds,name',
            'format' => 'nullable|in:facebook_csv,google_csv',
            'filters' => 'nullable|array',
            'field_mapping' => 'nullable|array',
            'schedule_cron' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (empty($data['format'])) {
            $data['format'] = 'facebook_csv';
        }

        // Store filters inside field_mapping['_filters'] as fallback if filters column is pending migration
        if (!empty($data['filters'])) {
            $mapping = $data['field_mapping'] ?? [];
            $mapping['_filters'] = $data['filters'];
            $data['field_mapping'] = $mapping;
        }

        $feed = Feed::create($data);

        // Generate the CSV file
        try {
            $url = $this->feedService->generate($feed);
            $feed->feed_url = $url;
        } catch (\Throwable $e) {
            \Log::error("Feed generation failed for {$feed->name}: " . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Feed created and generated successfully',
            'data' => $feed
        ]);
    }

    /**
     * Show a single feed.
     */
    public function show($id)
    {
        $feed = Feed::findOrFail($id);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($feed->name));
        $filePath = "feeds/{$safeName}.csv";
        $feed->file_exists = Storage::disk('public')->exists($filePath);
        $feed->feed_url = $feed->file_exists ? Storage::disk('public')->url($filePath) : null;

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
            'format' => 'sometimes|in:facebook_csv,google_csv',
            'filters' => 'nullable|array',
            'field_mapping' => 'nullable|array',
            'schedule_cron' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (isset($data['filters'])) {
            $mapping = $data['field_mapping'] ?? ($feed->field_mapping ?? []);
            $mapping['_filters'] = $data['filters'];
            $data['field_mapping'] = $mapping;
        }

        $feed->update($data);

        // Re-generate the feed
        try {
            $url = $this->feedService->generate($feed);
            $feed->feed_url = $url;
        } catch (\Throwable $e) {
            \Log::error("Feed regeneration failed for {$feed->name}: " . $e->getMessage());
        }

        return response()->json(['status' => 'success', 'data' => $feed]);
    }

    /**
     * Delete a feed and its associated CSV file.
     */
    public function destroy($id)
    {
        $feed = Feed::findOrFail($id);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($feed->name));
        Storage::disk('public')->delete("feeds/{$safeName}.csv");
        $feed->delete();

        return response()->json(['status' => 'success', 'message' => 'Feed deleted successfully']);
    }

    /**
     * Trigger on-demand generation of a feed.
     */
    public function generate($id)
    {
        $feed = Feed::findOrFail($id);
        $url = $this->feedService->generate($feed);

        return response()->json([
            'status' => 'success',
            'message' => 'Feed generated successfully',
            'feed_url' => $url
        ]);
    }

    /**
     * Preview products matching filters before generating CSV.
     */
    public function preview(Request $request)
    {
        $filters = [
            'category_ids' => $request->input('category_ids'),
            'category_id' => $request->input('category_id'),
            'stock_status' => $request->input('stock_status', 'all'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'id'),
            'sort_order' => $request->input('sort_order', 'desc'),
        ];

        $query = $this->feedService->queryProducts($filters);
        $total = $query->count();

        $previewProducts = $query->take(15)->get()->map(function ($p) {
            $base = (float) ($p->base_price ?: ($p->unit_price ?: ($p->sale_price ?: 0)));
            $sale = (float) ($p->sale_price ?: 0);
            $price = ($sale > 0 && $base > $sale) ? $base : ($sale > 0 ? $sale : $base);

            $frontendUrl = FeedGeneratorService::getFrontendUrl();
            $slug = !empty($p->slug) ? $p->slug : $p->id;

            return [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category?->name ?? 'General',
                'price' => number_format($price, 2, '.', '') . ' BDT',
                'sale_price' => $sale > 0 ? number_format($sale, 2, '.', '') . ' BDT' : null,
                'current_stock' => $p->current_stock,
                'availability' => $p->current_stock > 0 ? 'in stock' : 'out of stock',
                'image_url' => FeedGeneratorService::resolveImageUrl($p->image_url ?: $p->image),
                'link' => "{$frontendUrl}/products/{$slug}",
                'product_code' => $p->product_code,
            ];
        });

        return response()->json([
            'status' => 'success',
            'total_products' => $total,
            'preview_products' => $previewProducts,
        ]);
    }

    /**
     * Export / Download CSV directly with requested filters.
     */
    public function export(Request $request)
    {
        $filters = [
            'category_ids' => $request->input('category_ids'),
            'category_id' => $request->input('category_id'),
            'stock_status' => $request->input('stock_status', 'all'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by', 'id'),
            'sort_order' => $request->input('sort_order', 'desc'),
            'limit' => $request->input('limit'),
        ];

        $feedName = $request->input('feed_name') ?: 'product_catalog';
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($feedName));
        $campaign = $request->input('utm_campaign') ?: null;

        $csvContent = $this->feedService->generateCsvContent($filters, FeedGeneratorService::getDefaultMapping(), $campaign);

        $filename = "{$safeName}_" . date('Ymd_His') . ".csv";

        return new StreamedResponse(function () use ($csvContent) {
            echo $csvContent;
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ]);
    }

    /**
     * Fallback and direct server for feed files under /storage/feeds/{filename} or /feeds/{filename}
     */
    public function serveFeedFile($filename)
    {
        return $this->publicFeed($filename);
    }

    /**
     * Public feed endpoint for Meta / Facebook Catalog automated schedule synchronization.
     */
    public function publicFeed($name)
    {
        $cleanName = preg_replace('/\.csv$/i', '', $name);
        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($cleanName));
        $filePath = "feeds/{$safeName}.csv";

        $content = null;

        // 1. Check if the CSV exists in Storage public disk
        if (Storage::disk('public')->exists($filePath)) {
            $content = Storage::disk('public')->get($filePath);
        }
        // 2. Check if it exists directly in public/storage/feeds
        elseif (file_exists(public_path("storage/{$filePath}"))) {
            $content = file_get_contents(public_path("storage/{$filePath}"));
        }
        // 3. Check if it exists in storage/app/public/feeds
        elseif (file_exists(storage_path("app/public/{$filePath}"))) {
            $content = file_get_contents(storage_path("app/public/{$filePath}"));
        }

        // If not found on disk, find feed definition in DB or generate on the fly
        if (!$content) {
            $feed = Feed::where('name', $cleanName)
                ->orWhere('name', str_replace('_', ' ', $cleanName))
                ->orWhereRaw("REPLACE(LOWER(name), ' ', '_') = ?", [$safeName])
                ->orWhereRaw("LOWER(name) = ?", [strtolower($cleanName)])
                ->first();

            if ($feed) {
                try {
                    $this->feedService->generate($feed);
                    if (Storage::disk('public')->exists($filePath)) {
                        $content = Storage::disk('public')->get($filePath);
                    }
                } catch (\Throwable $e) {
                    \Log::error("Feed dynamic generation error for {$cleanName}: " . $e->getMessage());
                }

                if (!$content) {
                    $filters = $feed->filters ?? ($feed->field_mapping['_filters'] ?? []);
                    $mapping = !empty($feed->field_mapping) ? $feed->field_mapping : FeedGeneratorService::getDefaultMapping();
                    $content = $this->feedService->generateCsvContent($filters, $mapping, $safeName);
                }
            } else {
                // If feed not found in DB by exact name, parse heuristic filters from filename
                $filters = [];
                if (str_contains($safeName, 'in_stock')) {
                    $filters['stock_status'] = 'in_stock';
                } elseif (str_contains($safeName, 'out_of_stock')) {
                    $filters['stock_status'] = 'out_of_stock';
                }

                $content = $this->feedService->generateCsvContent($filters, FeedGeneratorService::getDefaultMapping(), $safeName);
            }

            // Save to public storage for subsequent static fast hits
            try {
                Storage::disk('public')->put($filePath, $content);
                $pubDir = public_path('storage/feeds');
                if (!file_exists($pubDir)) {
                    @mkdir($pubDir, 0775, true);
                }
                @file_put_contents("{$pubDir}/{$safeName}.csv", $content);
            } catch (\Throwable $e) {}
        }

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "inline; filename=\"{$safeName}.csv\"",
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
