<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MohasagorController1 extends Controller
{
    public function importProducts(Request $request)
    {
        Log::info('Starting Mohasagor product import');
        
        try {
            // Step 1: Fetch data from Mohasagor API
            Log::info('Fetching products from Mohasagor API');
            
            $response = Http::withHeaders([
                'api-key' => env('MOHASAGOR_API_KEY'),
                'secret-key' => env('MOHASAGOR_API_SECRET'),
                'Accept' => 'application/json',
            ])->get('https://mohasagor.com.bd/api/reseller/product');
            
            Log::info('API Response Status: ' . $response->status());
            
            if ($response->failed()) {
                Log::error('Mohasagor API request failed: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'Mohasagor API request failed',
                    'error' => $response->body(),
                ], $response->status());
            }
            
            $responseBody = $this->cleanResponse($response->body());
            $apiData = json_decode($responseBody, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to decode JSON: ' . json_last_error_msg());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to decode API response',
                    'error' => json_last_error_msg(),
                ], 500);
            }
            
            if (!isset($apiData['products'])) {
                Log::warning('No products key found in API response');
                return response()->json([
                    'success' => false,
                    'message' => 'No products found in API response structure',
                    'data' => $apiData,
                ]);
            }
            
            $products = $apiData['products'];
            Log::info('Found ' . count($products) . ' products to process');
            
            if (empty($products)) {
                return response()->json([
                    'success' => true,
                    'message' => 'No products found in the API',
                    'data' => [],
                    'stats' => [
                        'total' => 0,
                        'created' => 0,
                        'skipped' => 0,
                        'failed' => 0
                    ]
                ]);
            }
            
            $existingProducts = Product::whereNotNull('api_id')->pluck('api_id')->toArray();
            $existingProductIds = array_flip($existingProducts);
            $existingCategories = Category::all();
            $categoryCache = [];
            
            $stats = [
                'total' => count($products),
                'created' => 0,
                'skipped' => 0,
                'failed' => 0
            ];
            
            $failedProducts = [];
            $successProducts = [];
            
            foreach ($products as $index => $product) {
                if (!isset($product['id'])) {
                    $stats['failed']++;
                    $failedProducts[] = [
                        'index' => $index,
                        'name' => isset($product['name']) ? $this->cleanText($product['name']) : 'Unknown',
                        'reason' => 'Missing product ID'
                    ];
                    continue;
                }
                
                $productId = $product['id'];
                $productName = isset($product['name']) ? $this->cleanText($product['name']) : 'Unnamed Product';
                
                if (isset($existingProductIds[$productId])) {
                    $stats['skipped']++;
                    continue;
                }
                
                try {
                    $categoryName = isset($product['category']) ? $this->cleanText($product['category']) : null;
                    $categoryId = null;
                    
                    if ($categoryName) {
                        $categoryKey = strtolower(trim($categoryName));
                        
                        if (isset($categoryCache[$categoryKey])) {
                            $categoryId = $categoryCache[$categoryKey];
                        } else {
                            $existingCategory = $existingCategories->first(function ($cat) use ($categoryName) {
                                return strtolower(trim($this->cleanText($cat->name))) === strtolower(trim($categoryName));
                            });
                            
                            if ($existingCategory) {
                                $categoryId = $existingCategory->id;
                                $categoryCache[$categoryKey] = $categoryId;
                            } else {
                                $slug = isset($product['slug']) ? $this->cleanText($product['slug']) : Str::slug($categoryName);
                                $image = isset($product['thumbnail_img']) ? $this->cleanText($product['thumbnail_img']) : '';
                                
                                $newCategory = Category::create([
                                    'name' => trim($categoryName),
                                    'slug' => $slug,
                                    'image' => $image,
                                    'is_active' => true,
                                    'priority' => 1,
                                ]);
                                
                                $categoryId = $newCategory->id;
                                $categoryCache[$categoryKey] = $categoryId;
                                $existingCategories->push($newCategory);
                            }
                        }
                    }
                    
                    if (!$categoryId) {
                        throw new \Exception("No category ID for product");
                    }
                    
                    $productData = $this->prepareProductData($product, $categoryId);
                    
                    if (empty($productData['name']) || empty($productData['slug'])) {
                        throw new \Exception("Missing required fields (name or slug)");
                    }
                    
                    $newProduct = Product::create($productData);
                    
                    $existingProductIds[$productId] = true;
                    $stats['created']++;
                    
                    $successProducts[] = [
                        'id' => $newProduct->id,
                        'name' => $productName,
                        'api_id' => $productId
                    ];
                    
                    usleep(50000);
                } catch (\Exception $e) {
                    $stats['failed']++;
                    $failedProducts[] = [
                        'index' => $index,
                        'name' => $productName,
                        'api_id' => $productId,
                        'reason' => $e->getMessage(),
                        'category' => $categoryName ?? 'No category'
                    ];
                    Log::error("Error processing product {$productName} (ID: {$productId}): " . $e->getMessage());
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'stats' => $stats,
                'data' => [
                    'processed' => $stats['total'],
                    'success_count' => count($successProducts),
                    'failed_count' => count($failedProducts),
                    'failed_samples' => array_slice($failedProducts, 0, 10)
                ]
            ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (\Exception $e) {
            Log::error("Mohasagor import failed: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error during import: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }
    
    private function prepareProductData($product, $categoryId)
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
        $thumbnailImg = $this->saveImageLocally($this->cleanText($product['thumbnail_img'] ?? ''));
        
        $slug = !empty($product['slug'] ?? '') ? $this->cleanText($product['slug']) : Str::slug($productName);
        $originalSlug = $slug;
        $counter = 1;
        
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }
        
        // Build attributes
        $attributes = [];
        $variants = $product['product_variants'] ?? [];
        $attributeMap = [];
        
        foreach ($variants as $variant) {
            if (!empty($variant['attribute'] ?? '') && !empty($variant['variant'] ?? '')) {
                $name = $this->cleanText($variant['attribute']);
                $value = $this->cleanText($variant['variant']);
                $attributeMap[$name][] = $value;
            }
        }
        
        foreach ($attributeMap as $name => $values) {
            $attributes[] = ['name' => $name, 'values' => array_unique($values)];
        }
        
        // Get gallery images
        $galleryImages = $this->getGalleryImages($product);
        if (!empty($galleryImages)) {
            $galleryImages = array_slice($galleryImages, 0, 20); // max 20 images
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
            'image' => mb_substr($thumbnailImg, 0, 500, 'UTF-8'),
            'variations' => '[]', // Empty JSON array as string
            'attributes' => !empty($attributes) ? json_encode($attributes, JSON_UNESCAPED_SLASHES) : '[]',
            'colors' => '[]', // Empty JSON array as string
            'tags' => '[]', // Empty JSON array as string
            'is_featured' => false,
            'is_trending' => false,
            'is_discounted' => false,
            'status' => 'active',
            'slug' => mb_substr($slug, 0, 255, 'UTF-8'),
            'gallery_images' => !empty($galleryImages) ? json_encode($galleryImages, JSON_UNESCAPED_SLASHES) : '[]'
        ];
    }
    
    private function getGalleryImages($product)
    {
        $images = [];
        $productImages = $product['product_images'] ?? [];
        
        foreach ($productImages as $img) {
            $url = '';
            
            if (is_array($img) && isset($img['product_image'])) {
                $url = $img['product_image'];
            } elseif (is_string($img)) {
                $url = $img;
            }
            
            if (!empty($url) && $localPath = $this->saveImageLocally($url)) {
                $images[] = $localPath; // Save only the image name
            }
        }
        
        return $images;
    }
    
    private function cleanText($text)
    {
        if (!is_string($text)) return $text;
        
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        $text = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $text);
        $text = str_replace(["\xEF\xBB\xBF", "\xEF\xBF\xBD"], '', $text);
        
        return trim($text);
    }
    
    private function cleanResponse($response)
    {
        $response = str_replace("\xEF\xBB\xBF", '', $response);
        $response = mb_convert_encoding($response, 'UTF-8', 'UTF-8');
        $response = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '', $response);
        $response = str_replace("\xEF\xBF\xBD", '', $response);
        
        return $response;
    }
    
    private function saveImageLocally($url)
    {
        try {
            if (empty($url)) {
                return null;
            }
            
            $contents = file_get_contents($url);
            $name = basename($url); // Extract only the image name
            $path = 'products/' . $name;
            Storage::put($path, $contents);
            
            return $name; // Return only the image name
        } catch (\Exception $e) {
            Log::error("Failed to save image from URL {$url}: " . $e->getMessage());
            return null;
        }
    }
}