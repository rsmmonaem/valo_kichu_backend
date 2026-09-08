<?php

namespace App\Services;

use App\Models\Feed;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class FeedGeneratorService
{
    /**
     * Default field mapping for Facebook / Meta Catalog and Google Shopping CSV.
     */
    public static function getDefaultMapping(): array
    {
        return [
            'id' => 'id',
            'title' => 'name',
            'description' => 'description',
            'availability' => 'availability',
            'condition' => 'condition',
            'price' => 'price',
            'sale_price' => 'sale_price',
            'link' => 'link',
            'image_link' => 'image_link',
            'brand' => 'brand',
            'category' => 'category',
            'product_type' => 'category',
            'google_product_category' => 'category',
            'fb_product_category' => 'category',
            'item_group_id' => 'id',
            'gtin' => 'gtin',
            'mpn' => 'product_code',
        ];
    }

    /**
     * Resolve official frontend URL, ensuring https://valokichu.com is used instead of localhost.
     */
    public static function getFrontendUrl(): string
    {
        $url = env('FRONTEND_URL') ?: env('APP_FRONTEND_URL');
        if (empty($url) && function_exists('app') && app()->bound('config')) {
            $url = config('app.frontend_url');
        }

        if (empty($url) || str_contains($url, 'localhost')) {
            $url = 'https://valokichu.com';
        }

        return rtrim($url, '/');
    }

    /**
     * Resolve image URL ensuring hardcoded localhost/127.0.0.1 or relative paths
     * are converted to https://backend.valokichu.com.
     */
    public static function resolveImageUrl(?string $url): string
    {
        if (empty($url)) {
            return '';
        }

        $backendUrl = env('APP_URL');
        if (empty($backendUrl) && function_exists('app') && app()->bound('config')) {
            $backendUrl = config('app.url');
        }

        if (empty($backendUrl) || str_contains($backendUrl, 'localhost') || str_contains($backendUrl, '127.0.0.1')) {
            $backendUrl = 'https://backend.valokichu.com';
        }
        $backendUrl = rtrim($backendUrl, '/');

        // If it starts with localhost or 127.0.0.1, replace with official backend URL
        if (preg_match('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', $url)) {
            return preg_replace('#^https?://(localhost|127\.0\.0\.1)(:\d+)?#i', $backendUrl, $url);
        }

        // If it's already an external absolute URL (e.g. https://...), return it
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        // If it starts with /storage/ or storage/
        $cleanPath = ltrim($url, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            return "{$backendUrl}/{$cleanPath}";
        }

        return "{$backendUrl}/storage/{$cleanPath}";
    }

    /**
     * Generate a CSV feed for the given Feed configuration and store in public storage.
     */
    public function generate(Feed $feed): string
    {
        $mapping = !empty($feed->field_mapping) ? $feed->field_mapping : self::getDefaultMapping();
        $filters = $feed->filters ?? ($feed->field_mapping['_filters'] ?? []);

        $safeName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($feed->name));
        $csvContent = $this->generateCsvContent($filters, $mapping, $safeName);

        // Ensure storage feeds directory exists
        try {
            $dir = Storage::disk('public')->path('feeds');
            if (!file_exists($dir)) {
                @mkdir($dir, 0775, true);
            }
        } catch (\Throwable $e) {}

        // Store CSV in public/feeds directory
        $filename = "feeds/{$safeName}.csv";
        Storage::disk('public')->put($filename, $csvContent);

        // Also write directly to public/storage/feeds if available as web root fallback
        try {
            $publicFeedsDir = public_path('storage/feeds');
            if (!file_exists($publicFeedsDir)) {
                @mkdir($publicFeedsDir, 0775, true);
            }
            if (file_exists($publicFeedsDir) && is_writable($publicFeedsDir)) {
                @file_put_contents("{$publicFeedsDir}/{$safeName}.csv", $csvContent);
            }
        } catch (\Throwable $e) {}

        // Update feed metadata
        $feed->last_generated_at = now();
        $feed->save();

        $baseUrl = rtrim(config('app.url') ?: env('APP_URL', 'https://backend.valokichu.com'), '/');
        return "{$baseUrl}/storage/feeds/{$safeName}.csv";
    }

    /**
     * Query products based on user-selected filters (single/multiple categories, stock status, sorting, etc.).
     */
    public function queryProducts(array $filters = [])
    {
        $query = Product::withoutGlobalScope('in_stock')
            ->with(['category', 'brand'])
            ->where('is_active', true);

        // Category filtering (single category or multiple categories)
        if (!empty($filters['category_ids'])) {
            $catIds = $filters['category_ids'];
            if (!is_array($catIds)) {
                $catIds = array_map('trim', explode(',', (string) $catIds));
            }
            $catIds = array_filter(array_map('intval', $catIds));

            if (!empty($catIds)) {
                $allCategoryIds = [];
                foreach ($catIds as $cid) {
                    $allCategoryIds = array_merge($allCategoryIds, Category::getAllChildCategoryIds($cid));
                }
                $allCategoryIds = array_values(array_unique($allCategoryIds));
                $query->whereIn('category_id', $allCategoryIds);
            }
        } elseif (!empty($filters['category_id'])) {
            $cid = (int) $filters['category_id'];
            if ($cid > 0) {
                $allCategoryIds = Category::getAllChildCategoryIds($cid);
                $query->whereIn('category_id', $allCategoryIds);
            }
        }

        // Stock status filter: 'in_stock' (default for ads), 'all', or 'out_of_stock'
        $stockStatus = $filters['stock_status'] ?? 'all';
        if ($stockStatus === 'in_stock') {
            $query->where('current_stock', '>', 0);
        } elseif ($stockStatus === 'out_of_stock') {
            $query->where('current_stock', '<=', 0);
        }

        // Optional keyword search
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%")
                  ->orWhere('product_sku', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        switch ($sortBy) {
            case 'name':
                $query->orderBy('name', $sortOrder);
                break;
            case 'price':
                $query->orderBy('sale_price', $sortOrder)->orderBy('base_price', $sortOrder);
                break;
            case 'stock':
            case 'current_stock':
                $query->orderBy('current_stock', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default:
                $query->orderBy('id', $sortOrder);
                break;
        }

        if (!empty($filters['limit']) && is_numeric($filters['limit'])) {
            $query->limit((int) $filters['limit']);
        }

        return $query;
    }

    /**
     * Generate raw CSV string from filters and mapping using native PHP CSV functions.
     */
    public function generateCsvContent(array $filters = [], array $mapping = [], ?string $campaignName = null): string
    {
        if (empty($mapping)) {
            $mapping = self::getDefaultMapping();
        }

        // Exclude internal control keys from CSV headers
        $cleanMapping = array_filter($mapping, function ($key) {
            return !str_starts_with($key, '_');
        }, ARRAY_FILTER_USE_KEY);

        $products = $this->queryProducts($filters)->get();

        $handle = fopen('php://temp', 'r+');

        // CSV Header: Facebook Catalog expects column names as keys
        fputcsv($handle, array_keys($cleanMapping));

        foreach ($products as $product) {
            $row = [];
            foreach ($cleanMapping as $column => $attribute) {
                $row[] = $this->resolveColumnValue($product, $column, $attribute, $campaignName);
            }
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return $csvContent;
    }

    /**
     * Resolve a single column value for a product conforming to Facebook/Google specifications.
     */
    private function resolveColumnValue(Product $product, string $column, string $attribute, ?string $campaignName = null): string
    {
        switch ($column) {
            case 'id':
                return (string) $product->id;

            case 'title':
                return trim(strip_tags($product->name ?? ''));

            case 'description':
                $desc = strip_tags($product->description ?? $product->short_description ?? $product->name ?? '');
                $desc = html_entity_decode($desc, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                $desc = preg_replace('/\s+/', ' ', trim($desc));
                if (empty($desc)) {
                    $desc = $product->name ?? 'Product';
                }
                if (mb_strlen($desc) > 5000) {
                    $desc = mb_substr($desc, 0, 4997) . '...';
                }
                return $desc;

            case 'availability':
                return ($product->current_stock > 0) ? 'in stock' : 'out of stock';

            case 'condition':
                return 'new';

            case 'price':
                // Regular price with currency code (e.g. 1200.00 BDT)
                $base = (float) ($product->base_price ?: ($product->unit_price ?: ($product->sale_price ?: 0)));
                $sale = (float) ($product->sale_price ?: 0);
                // If on sale and base price exists, price is original base price
                $price = ($sale > 0 && $base > $sale) ? $base : ($sale > 0 ? $sale : ($base > 0 ? $base : (float)($product->purchase_price ?? 0)));
                return number_format($price, 2, '.', '') . ' BDT';

            case 'sale_price':
                // Discounted price with currency code (e.g. 990.00 BDT)
                $base = (float) ($product->base_price ?: ($product->unit_price ?: 0));
                $sale = (float) ($product->sale_price ?: 0);
                if ($sale > 0 && $base > 0 && $sale < $base) {
                    return number_format($sale, 2, '.', '') . ' BDT';
                }
                $fallback = $sale > 0 ? $sale : ($base > 0 ? $base : (float)($product->purchase_price ?? 0));
                return number_format($fallback, 2, '.', '') . ' BDT';

            case 'link':
                $frontendUrl = self::getFrontendUrl();
                $slug = !empty($product->slug) ? $product->slug : $product->id;
                $link = "{$frontendUrl}/products/{$slug}";
                // Only append UTM if an explicit non-default campaign name was requested
                if (!empty($campaignName) && !in_array(strtolower($campaignName), ['none', 'clean', 'catalog', 'facebook_feed', 'facebook_catalog'])) {
                    return $this->buildLinkWithUtm($link, $campaignName);
                }
                return $link;

            case 'image_link':
                $img = $product->image_url ?: $product->image;
                return self::resolveImageUrl($img);

            case 'brand':
                $brandName = '';
                if ($product->relationLoaded('brand') && $product->brand) {
                    $brandName = $product->brand->name ?? '';
                } elseif (!empty($product->brand) && is_string($product->brand)) {
                    $brandName = $product->brand;
                }
                return !empty($brandName) ? $brandName : config('app.name', 'Valokichu');

            case 'category':
            case 'product_type':
            case 'google_product_category':
            case 'fb_product_category':
                return $this->resolveCategoryName($product);

            case 'item_group_id':
                return (string) $product->id;

            case 'gtin':
                return (string) ($product->gtin ?? $product->product_sku ?? '');

            case 'mpn':
                return (string) ($product->product_code ?? $product->product_sku ?? $product->id);

            default:
                // Fallback for custom mapped fields
                $val = $product->{$attribute} ?? null;
                if (is_array($val)) {
                    return json_encode($val);
                }
                return (string) ($val ?? '');
        }
    }

    /**
     * Resolve category hierarchy or name.
     */
    private function resolveCategoryName(Product $product): string
    {
        if ($product->relationLoaded('category') && $product->category) {
            return $product->category->name ?? '';
        }

        if (!empty($product->category_id)) {
            $cat = Category::find($product->category_id);
            if ($cat) {
                return $cat->name;
            }
        }

        $rawCat = $product->getRawOriginal('category');
        if (!empty($rawCat) && is_string($rawCat)) {
            return $rawCat;
        }

        return 'General';
    }

    /**
     * Append UTM parameters for Facebook catalog tracking.
     */
    private function buildLinkWithUtm(?string $baseUrl, ?string $feedName): string
    {
        if (empty($baseUrl)) {
            return '';
        }

        $campaign = preg_replace('/[^a-zA-Z0-9_\-]/', '_', strtolower($feedName ?: 'catalog'));

        $utm = http_build_query([
            'utm_source' => 'facebook',
            'utm_medium' => 'catalog',
            'utm_campaign' => $campaign,
        ]);

        return $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . $utm;
    }
}
