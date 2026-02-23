<?php

namespace App\Jobs;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DownloadImportedProductImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $product;
    protected $imageUrl;
    protected $galleryImages;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Product $product, $imageUrl, array $galleryImages = [])
    {
        $this->product = $product;
        $this->imageUrl = $imageUrl;
        $this->galleryImages = $galleryImages;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Job Start: Processing images for Product ID: {$this->product->id} slug: {$this->product->slug}");

        // Process Main Image
        if (!empty($this->imageUrl)) {
            Log::info("Job: Downloading main image for product {$this->product->id}: {$this->imageUrl}");
            $localImage = $this->downloadAndOptimizeImage($this->imageUrl);
            if ($localImage) {
                $this->product->image = $localImage;

                 // Sync with Category if category has no image or has external image
                 if ($this->product->category) {
                     $catImage = $this->product->category->image;
                     if (empty($catImage) || str_starts_with($catImage, 'http')) {
                        $this->product->category->image = $localImage;
                        $this->product->category->save();
                        Log::info("Job Success: Updated Category {$this->product->category->id} image to local file: {$localImage}");
                     }
                 }
            } else {
                Log::warning("Job Warning: Local main image failed to save for product {$this->product->id}");
            }
        }

        // Process Gallery Images
        $processedGallery = [];
        foreach ($this->galleryImages as $img) {
            $url = '';
            if (is_array($img) && isset($img['product_image'])) {
                $url = $img['product_image'];
            } elseif (is_string($img)) {
                $url = $img;
            }

            if (!empty($url)) {
                // If it's already a local path, keep it
                if (!str_starts_with($url, 'http')) {
                    $processedGallery[] = $url;
                    continue;
                }

                $localPath = $this->downloadAndOptimizeImage($url);
                if ($localPath) {
                    $processedGallery[] = $localPath;
                }
            }
        }

        if (!empty($processedGallery)) {
            $this->product->gallery_images = json_encode($processedGallery); 
        }
        
        if ($this->product->isDirty(['image', 'gallery_images'])) {
            $this->product->save();
            Log::info("Job Success: All images localized/optimized for Product ID: {$this->product->id}");
        }
    }

    protected function downloadAndOptimizeImage($url)
    {
        try {
            // Basic validation
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                Log::error("Job: Invalid image URL: {$url}");
                return null;
            }

            // Using stream context to set a timeout
            $context = stream_context_create(['http' => ['timeout' => 15]]);
            $contents = file_get_contents($url, false, $context);
            
            if ($contents === false) {
                 Log::error("Job: Failed to download image from: {$url}");
                 return null;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($contents);

            // Resize constraint: max width 800px, maintain aspect ratio
            if ($image->width() > 800) {
                $image->scale(width: 800);
            }

            // Name logic: ss + basename of original URL
            $originalName = basename($url);
            $name = "ss{$originalName}";
            
            // Ensure extension is webp if we are converting, or keep original if we change encoding
            // The user wants "same name", so I will save with original name but potentially optimized content.
            // If I use toWebp, I should probably use .webp extension, but the DB already has the original extension.
            // To be safe and satisfy "same name", I will encode to original extension or just use original content if possible.
            
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if (in_array($extension, ['jpg', 'jpeg'])) {
                $encoded = $image->toJpeg(quality: 80);
            } elseif ($extension === 'png') {
                $encoded = $image->toPng();
            } else {
                $encoded = $image->toWebp(quality: 80);
                // If it was already something else, maybe we should have updated the extension in DB too.
                // But let's try to keep it simple and match the DB.
            }
            
            $path = 'products/' . $name;

            // Save to public disk (storage/app/public/products/)
            if (Storage::disk('public')->exists($path)) {
                Log::info("Job: Image already optimized exists: {$name}");
                return $originalName;
            }

            $saved = Storage::disk('public')->put($path, (string) $encoded);
            
            if ($saved) {
                Log::info("Job Success: Saved locally: {$path}");
                return $originalName;
            } else {
                Log::error("Job Error: Failed to write file to disk for: {$path}");
                return null;
            }

        } catch (\Exception $e) {
            Log::error("Job: Failed to optimize image {$url}: " . $e->getMessage());
            return null;
        }
    }
}
