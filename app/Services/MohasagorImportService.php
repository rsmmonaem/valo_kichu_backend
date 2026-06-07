<?php

namespace App\Services;

use App\Jobs\DownloadImportedProductImage;
use App\Models\Category;
use App\Models\Product;
use App\Models\Image as ImageModel;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class MohasagorImportService
{
    protected array $categoryCache = [];
    protected $existingCategories;
    protected $baseUrl = 'https://mohasagor.com.bd/api/reseller/product';

    public function __construct()
    {
        $this->existingCategories = Category::all();
    }

    /**
     * Stream the import process, yielding status updates.
     */
    public function importStream()
    {
        Log::info('Starting Mohasagor product import stream');
        yield ['type' => 'info', 'message' => 'Initiating import process...'];

        $page = 1;
        $lastPage = 1;

        // First request to determine total pages and process first batch
        $response = $this->fetchPage($page);

        if (!$response['success']) {
            yield ['type' => 'error', 'message' => 'Failed to connect to API: ' . $response['message']];
            return;
        }

        if (isset($response['pagination'])) {
            $lastPage = $response['pagination']['last_page'] ?? 1;
            yield ['type' => 'info', 'message' => "Found {$response['pagination']['total']} products across {$lastPage} pages."];
        }

        do {
            yield ['type' => 'progress', 'page' => $page, 'total_pages' => $lastPage, 'message' => "Processing Page {$page}/{$lastPage}..."];

            if ($page > 1) {
                // Fetch subsequent pages
                $response = $this->fetchPage($page);
                if (!$response['success']) {
                    yield ['type' => 'error', 'message' => "Failed to fetch page {$page}. Skipping."];
                    $page++;
                    continue;
                }
            }

            $products = $response['products'] ?? [];
            if (empty($products)) {
                yield ['type' => 'warning', 'message' => "No products found on page {$page}."];
            } else {
                $stats = $this->processBatch($products);
                yield [
                    'type' => 'batch_stats',
                    'page' => $page,
                    'created' => $stats['created'],
                    'updated' => $stats['updated'],
                    'failed' => $stats['failed'],
                    'skipped' => $stats['skipped'],
                    'message' => "Page {$page}: Created {$stats['created']}, Updated {$stats['updated']}, Skipped {$stats['skipped']}"
                ];
            }

            $page++;
            // Optional: small delay to prevent rate limiting
            // usleep(100000); 

        } while ($page <= $lastPage);

        yield ['type' => 'done', 'message' => 'Import process completed successfully.'];
    }

    /**
     * Backward compatibility wrapper (though controller should use stream)
     */
    public function fetchAndProcessProducts()
    {
        $stats = ['total' => 0, 'created' => 0, 'failed' => 0];
        foreach ($this->importStream() as $update) {
            // consuming the generator
            if (isset($update['type']) && $update['type'] === 'batch_stats') {
                $stats['created'] += $update['created'] ?? 0;
            }
        }
        return ['success' => true, 'message' => 'Import completed via stream wrapper', 'stats' => $stats];
    }

    protected function fetchPage($page)
    {
        try {
            $response = Http::withHeaders([
                'api-key' => env('MOHASAGOR_API_KEY'),
                'secret-key' => env('MOHASAGOR_API_SECRET'),
                'Accept' => 'application/json',
            ])->get($this->baseUrl, ['page' => $page]);

            if ($response->failed()) {
                Log::error("Mohasagor API request failed for page {$page}: " . $response->body());
                return ['success' => false, 'message' => $response->status()];
            }

            $body = $this->cleanResponse($response->body());
            $data = json_decode($body, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['success' => false, 'message' => 'JSON Decode Error'];
            }

            return [
                'success' => true,
                'products' => $data['products'] ?? [],
                'pagination' => $data['pagination'] ?? null
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function processBatch(array $products)
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($products as $productData) {
            if (!isset($productData['id'])) {
                $stats['failed']++;
                continue;
            }

            try {
                $apiId = $productData['id'];
                
                $categoryId = $this->resolveCategory($productData);
                $prepared = $this->prepareProductData($productData, $categoryId);
                
                if (empty($prepared['name']) || empty($prepared['slug'])) {
                    $stats['failed']++;
                    continue;
                }

                // Check for existing product by API ID
                $existingProduct = Product::where('api_id', $apiId)->exists();

                if ($existingProduct) {
                    // User Request: "duplicate product will not import"
                    // We skip if it already exists.
                    $stats['skipped']++;
                    continue; 
                }

                $product = Product::create($prepared);
                $stats['created']++;

                // Dispatch Image Job
                $mainImageUrl = $productData['thumbnail_img'] ?? null;
                $galleryPayload = $productData['product_images'] ?? [];
                
                if ($mainImageUrl || !empty($galleryPayload)) {
                    DownloadImportedProductImage::dispatch($product, $mainImageUrl, $galleryPayload);
                }

            } catch (\Exception $e) {
                Log::error("Failed to process product {$productData['id']}: " . $e->getMessage());
                $stats['failed']++;
            }
        }

        return $stats;
    }

    /**
     * Clear all products and categories to start fresh.
     */
    public function resetData()
    {
        Schema::disableForeignKeyConstraints();

        Product::truncate();
        Category::truncate();
        
        // Truncate other tables if exist and related
        if (Schema::hasTable('product_variations')) {
             \Illuminate\Support\Facades\DB::table('product_variations')->truncate(); 
        }
         if (Schema::hasTable('images')) {
             // Assuming images table stores polymorphic relations
              \Illuminate\Support\Facades\DB::table('images')->truncate(); 
        }
        
        Schema::enableForeignKeyConstraints();
        
        // Clear usage caches
        $this->categoryCache = [];
        $this->existingCategories = Category::all();
        
        return true;
    }

    protected function resolveCategory($product)
    {
        $categoryName = isset($product['category']) ? $this->cleanText($product['category']) : null;
        
        if (!$categoryName) {
            throw new \Exception("No category name provided");
        }

        $categoryKey = strtolower(trim($categoryName));

        if (isset($this->categoryCache[$categoryKey])) {
            return $this->categoryCache[$categoryKey];
        }
        
        $existing = $this->existingCategories->first(function($cat) use ($categoryKey) {
            return strtolower(trim($this->cleanText($cat->name))) === $categoryKey;
        });

        if ($existing) {
            $this->categoryCache[$categoryKey] = $existing->id;
            return $existing->id;
        }

        $slug = isset($product['slug']) ? $this->cleanText($product['slug']) : Str::slug($categoryName);
        
        $newCategory = Category::create([
            'name' => trim($categoryName),
            'slug' => $slug,
            'image' => !empty($product['thumbnail_img']) ? basename($product['thumbnail_img']) : '', 
            'is_active' => true,
            'priority' => 1,
        ]);
        
        $this->existingCategories->push($newCategory);
        $this->categoryCache[$categoryKey] = $newCategory->id;
        
        return $newCategory->id;
    }

    protected function prepareProductData($product, $categoryId)
    {
        $productName = $this->cleanText($product['name'] ?? 'Unnamed Product');
        $description = $this->cleanText($product['details'] ?? 'No description available');
        $brand = $this->cleanText($product['brand'] ?? 'Unknown');
        $productId = $product['id'] ?? time();
        $sku = $this->cleanText($product['sku'] ?? 'SKU-' . $productId);
        $unit = $this->cleanText($product['unit'] ?? 'pcs');
        $price = floatval($product['price'] ?? 0);
        $purchasePrice = floatval($product['sale_price'] ?? 0);
        $unitPrice = floatval($product['unit_price'] ?? 0);
        $minOrderQty = intval($product['min_order_qty'] ?? 1);
        $currentStock = intval($product['current_stock'] ?? 0);
        $discountAmount = floatval($product['discount_amount'] ?? 0);
        $taxAmount = floatval($product['tax_amount'] ?? 0);
        $shippingCost = floatval($product['shipping_cost'] ?? 0);
        
        // Handle Slug
        $slug = !empty($product['slug'] ?? '') ? $this->cleanText($product['slug']) : Str::slug($productName);
        $originalSlug = $slug;
        $counter = 1;
        // Check uniqueness if new or if slug changed significantly? 
        while (Product::where('slug', $slug)->where('api_id', '!=', $productId)->exists()) {
             $slug = $originalSlug . '-' . $counter++;
        }

        // Attributes
        $attributes = [];
        $variants = $product['product_variants'] ?? [];
        $attributeMap = [];
        foreach ($variants as $variant) {
            if (!empty($variant['attribute']) && !empty($variant['variant'])) {
                $name = $this->cleanText($variant['attribute']);
                $value = $this->cleanText($variant['variant']);
                $attributeMap[$name][] = $value;
            }
        }
        foreach ($attributeMap as $name => $values) {
            $attributes[] = ['name' => $name, 'values' => array_unique($values)];
        }

        // Initial image data - use external URLs for immediate display
        $mainImage = !empty($product['thumbnail_img']) ? basename($product['thumbnail_img']) : '';
        $galleryImages = [];
        if (isset($product['product_images']) && is_array($product['product_images'])) {
            foreach ($product['product_images'] as $img) {
                if (is_array($img) && isset($img['product_image'])) {
                    $galleryImages[] = basename($img['product_image']);
                } elseif (is_string($img)) {
                    $galleryImages[] = basename($img);
                }
            }
        }

        return [
            'name' => mb_substr($productName, 0, 255, 'UTF-8'),
            'description' => mb_substr($description, 0, 5000, 'UTF-8'),
            'category_id' => $categoryId,
            'brand' => mb_substr($brand, 0, 255, 'UTF-8'),
            'api_id' => $productId,
            'api_from' => 'Mohasagor',
            'product_code' => $this->cleanText($product['product_code'] ?? null),
            'product_type' => 'physical',
            'product_sku' => mb_substr($sku, 0, 50, 'UTF-8'),
            'unit' => mb_substr($unit, 0, 20, 'UTF-8'),
            'base_price' => $price,
            'purchase_price' => $purchasePrice,
            'unit_price' => $unitPrice,
            'min_order_qty' => $minOrderQty,
            'current_stock' => $currentStock,
            'discount_type' => 'None',
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'tax_calculation' => 'exclude',
            'shipping_cost' => $shippingCost,
            'shipping_multiply' => false,
            'loyalty_point' => 0,
            'image' => $mainImage, 
            'variations' => '[]',
            'attributes' => !empty($attributes) ? json_encode($attributes, JSON_UNESCAPED_SLASHES) : '[]',
            'colors' => '[]',
            'tags' => '[]',
            'is_featured' => false,
            'is_trending' => false,
            'is_discounted' => false,
            'status' => 'active',
            'slug' => mb_substr($slug, 0, 255, 'UTF-8'),
            'gallery_images' => json_encode($galleryImages),
        ];
    }

    protected function cleanText($text)
    {
        if (!is_string($text)) return $text;
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        return trim(str_replace(["\xEF\xBB\xBF", "\xEF\xBF\xBD"], '', $text));
    }

    protected function cleanResponse($response)
    {
        $response = str_replace("\xEF\xBB\xBF", '', $response);
        $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');
        $response = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $response);
        return str_replace("\xEF\xBF\xBD", '', $response);
    }
}
