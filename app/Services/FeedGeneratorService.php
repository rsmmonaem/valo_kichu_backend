<?php

namespace App\Services;

use App\Models\Feed;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use SplTempFileObject;

class FeedGeneratorService
{
    /**
     * Generate a CSV feed for the given Feed configuration.
     */
    public function generate(Feed $feed): string
    {
        // Determine columns based on field mapping or defaults for Facebook
        $defaultMapping = [
            'id' => 'id',
            'title' => 'name',
            'description' => 'description',
            'availability' => 'current_stock',
            'condition' => 'condition', // assume a column exists or default to 'new'
            'price' => 'sale_price',
            'link' => 'link', // we will build link with UTM params
            'image_link' => 'image',
            'brand' => 'brand',
            'gtin' => 'gtin',
            'mpn' => 'product_code',
        ];

        $mapping = $feed->field_mapping ? $feed->field_mapping : $defaultMapping;

        // Fetch all active products (respecting global scope)
        $products = Product::where('is_active', true)->get();

        // Create CSV writer (in-memory)
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->setDelimiter(',');

        // Header row
        $csv->insertOne(array_values($mapping));

        foreach ($products as $product) {
            $row = [];
            foreach ($mapping as $column => $attribute) {
                $value = $this->resolveAttribute($product, $attribute);
                // Special handling for link to add UTM params for carousel ads
                if ($column === 'link') {
                    $value = $this->buildLinkWithUtm($value, $feed->name);
                }
                // Image link must be a full URL
                if ($column === 'image_link') {
                    $value = $product->image_url;
                }
                // Availability must be "in stock" or "out of stock"
                if ($column === 'availability') {
                    $value = $product->current_stock > 0 ? 'in stock' : 'out of stock';
                }
                // Condition default
                if ($column === 'condition') {
                    $value = $value ?? 'new';
                }
                $row[] = $value;
            }
            $csv->insertOne($row);
        }

        // Store CSV in public/feeds directory
        $filename = "feeds/{$feed->name}.csv";
        Storage::disk('public')->put($filename, $csv->getContent());

        // Update feed metadata
        $feed->last_generated_at = now();
        $feed->save();

        return Storage::disk('public')->url($filename);
    }

    /**
     * Resolve attribute value from Product model, supporting nested JSON attributes.
     */
    private function resolveAttribute(Product $product, string $attribute)
    {
        // Direct attribute on the model
        if ($product->hasAttribute($attribute)) {
            return $product->{$attribute};
        }
        // Fallback to accessor if defined (e.g., price)
        $accessor = "get" . ucfirst($attribute) . "Attribute";
        if (method_exists($product, $accessor)) {
            return $product->{$attribute};
        }
        return null;
    }

    /**
     * Append UTM parameters for Facebook carousel tracking.
     */
    private function buildLinkWithUtm(?string $baseUrl, string $feedName): ?string
    {
        if (empty($baseUrl)) {
            return $baseUrl;
        }
        
        $utm = http_build_query([
            'utm_source' => 'facebook',
            'utm_medium' => 'carousel',
            'utm_campaign' => $feedName,
        ]);
        return $baseUrl . (strpos($baseUrl, '?') === false ? "?" : "&") . $utm;
    }
}
?>
