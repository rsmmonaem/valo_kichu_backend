<?php

namespace App\Services;

use App\Models\Barcode;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BarcodeService
{
    /**
     * Generate barcode for a product
     */
    public function generateBarcode($productId, $branchId = null, $barcodeType = 'CODE128', $format = 'png')
    {
        $product = Product::findOrFail($productId);
        
        // Require product code (SKU) as barcode value
        if (empty($product->product_code)) {
            throw new \Exception('Product must have a product code (SKU) to generate barcode.');
        }
        
        $barcodeValue = $product->product_code;
        
        // Check if barcode already exists for this product and branch combination
        $existingBarcode = Barcode::where('barcode', $barcodeValue)
            ->where('product_id', $productId)
            ->where('branch_id', $branchId)
            ->where('barcode_type', $barcodeType)
            ->first();
        
        if ($existingBarcode) {
            return $existingBarcode;
        }

        // Generate barcode image path
        $barcodeImagePath = $this->saveBarcodeImage($barcodeValue, $barcodeType, $format);

        $barcode = Barcode::create([
            'product_id' => $productId,
            'branch_id' => $branchId,
            'barcode' => $barcodeValue,
            'barcode_type' => $barcodeType,
            'format' => $format,
            'barcode_image_path' => $barcodeImagePath,
            'created_by' => auth('admin')->id(),
        ]);

        return $barcode;
    }

    /**
     * Save barcode image
     * Uses barcode API or generates path for client-side rendering
     */
    private function saveBarcodeImage($barcodeValue, $barcodeType = 'CODE128', $format = 'png')
    {
        // Generate filename based on barcode value, type, and format
        $safeValue = preg_replace('/[^a-zA-Z0-9]/', '_', $barcodeValue);
        $filename = 'barcodes/' . $safeValue . '_' . $barcodeType . '.' . $format;
        
        // For QR codes, we can use a QR code API
        if ($barcodeType === 'QR') {
            // Generate QR code using API
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($barcodeValue);
            try {
                $imageData = file_get_contents($qrUrl);
                Storage::disk('public')->put($filename, $imageData);
            } catch (\Exception $e) {
                // If API fails, just save the path - will be generated client-side
                Storage::disk('public')->put($filename, '');
            }
        } else {
            // For other barcode types, save path - will be generated client-side using JsBarcode
            // Or use a barcode API like: https://barcode.tec-it.com/barcode.ashx?data={data}&code={type}
            $barcodeApiUrl = 'https://barcode.tec-it.com/barcode.ashx?data=' . urlencode($barcodeValue) . '&code=' . $barcodeType . '&dpi=96&dataseparator=';
            try {
                $imageData = file_get_contents($barcodeApiUrl);
                Storage::disk('public')->put($filename, $imageData);
            } catch (\Exception $e) {
                // If API fails, just save the path - will be generated client-side
                Storage::disk('public')->put($filename, '');
            }
        }
        
        return $filename;
    }

    /**
     * Generate barcodes for multiple products
     */
    public function generateBulkBarcodes($productIds, $branchId = null, $barcodeType = 'CODE128')
    {
        $barcodes = [];
        
        foreach ($productIds as $productId) {
            try {
                $barcode = $this->generateBarcode($productId, $branchId, $barcodeType);
                $barcodes[] = $barcode;
            } catch (\Exception $e) {
                // Log error and continue
                \Log::error("Failed to generate barcode for product {$productId}: " . $e->getMessage());
            }
        }

        return $barcodes;
    }
}

