<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Models\ProductImage;
use App\Models\ProductVariation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductService
{
    protected $repository;

    public function __construct(ProductRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getAll(Request $request)
    {
        $query = $this->repository->getWithRelations();

        if ($request->has('search')) {
            $query = $this->repository->search($request->search);
        }

        return $query->orderBy('created_at', 'desc')->paginate(20);
    }

    public function find(int $id, array $relations = [])
    {
        $query = $this->repository->getModel();
        if (!empty($relations)) {
            $query = $query->with($relations);
        }
        return $query->findOrFail($id);
    }

    public function create(array $data, $images = [])
    {
        // Convert tags string to array if provided
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_filter(array_map('trim', explode(',', $data['tags'])));
        }

        // Determine the final category_id based on hierarchy
        // Priority: sub_sub_category_id > sub_category_id > category_id
        $category_ids = [];
        if (!empty($data['category_id'])) {
            $category_ids[] = $data['category_id'];
        }
        if (!empty($data['sub_category_id'])) {
            $category_ids[] = $data['sub_category_id'];
        }
        if (!empty($data['sub_sub_category_id'])) {
            $category_ids[] = $data['sub_sub_category_id'];
        } 
        $data['category_ids'] = $category_ids;
        // Set vendor_id to null for in-house products
        if (!isset($data['vendor_id']) || empty($data['vendor_id'])) {
            $data['vendor_id'] = null;
        }

        // Set default discount_type if empty (database requires NOT NULL)
        if (empty($data['discount_type'])) {
            $data['discount_type'] = 'amount';
        }

        // Set discount to 0 if discount_type is set but discount is empty
        if (!empty($data['discount_type']) && (!isset($data['discount']) || $data['discount'] === '')) {
            $data['discount'] = 0;
        }

        // Handle boolean fields
        $data['is_available'] = isset($data['is_available']) ? true : false;
        $data['multiply_cost_by_quantity'] = isset($data['multiply_cost_by_quantity']) ? true : false;

        // Handle meta_image upload
        if (isset($data['meta_image']) && $data['meta_image'] instanceof \Illuminate\Http\UploadedFile) {
            $path = $data['meta_image']->store('products', 'public');
            if ($path) {
                $data['meta_image'] = $path;
            } else {
                unset($data['meta_image']);
            }
        } elseif (empty($data['meta_image'])) {
            unset($data['meta_image']);
        }

        // Create product
        $product = $this->repository->create($data);

        // Handle image uploads
        if (!empty($images)) {
            $this->uploadImages($product->id, $images);
        }

        // Handle variants/variations
        if (isset($data['variations']) && is_array($data['variations']) && !empty($data['variations'])) {
            $this->saveVariants($product->id, $data['variations'], $data['selected_attributes'] ?? null, false);
        }

        return $product;
    }

    public function update(int $id, array $data, $images = [], $deleteImages = [])
    {
        // Convert tags string to array if provided
        if (isset($data['tags']) && is_string($data['tags'])) {
            $data['tags'] = array_filter(array_map('trim', explode(',', $data['tags'])));
        }

        $category_ids = [];
        if (!empty($data['category_id'])) {
            $category_ids[] = $data['category_id'];
        }
        if (!empty($data['sub_category_id'])) {
            $category_ids[] = $data['sub_category_id'];
        }
        if (!empty($data['sub_sub_category_id'])) {
            $category_ids[] = $data['sub_sub_category_id'];
        } 
        $data['category_ids'] = $category_ids;

        // Set vendor_id to null for in-house products if not provided
        if (!isset($data['vendor_id']) || empty($data['vendor_id'])) {
            $data['vendor_id'] = null;
        }

        // Set default discount_type if empty (database requires NOT NULL)
        if (empty($data['discount_type'])) {
            $data['discount_type'] = 'amount';
        }

        // Set discount to 0 if discount_type is set but discount is empty
        if (!empty($data['discount_type']) && (!isset($data['discount']) || $data['discount'] === '')) {
            $data['discount'] = 0;
        }

        // Handle boolean fields
        $data['is_available'] = isset($data['is_available']) ? true : false;
        $data['multiply_cost_by_quantity'] = isset($data['multiply_cost_by_quantity']) ? true : false;

        // Handle meta_image upload
        if (isset($data['meta_image']) && $data['meta_image'] instanceof \Illuminate\Http\UploadedFile) {
            // Delete old meta image if exists
            $product = $this->repository->find($id);
            if ($product->meta_image && Storage::disk('public')->exists($product->meta_image)) {
                Storage::disk('public')->delete($product->meta_image);
            }
            
            $path = $data['meta_image']->store('products', 'public');
            if ($path) {
                $data['meta_image'] = $path;
            } else {
                unset($data['meta_image']);
            }
        } elseif (empty($data['meta_image'])) {
            // Keep existing meta_image if not provided
            unset($data['meta_image']);
        }

        // Update product
        $this->repository->update($id, $data);

        // Delete specified images
        if (!empty($deleteImages)) {
            $this->deleteImages($deleteImages);
        }

        // Handle new image uploads
        if (!empty($images)) {
            $this->uploadImages($id, $images);
        }

        // Handle variants/variations
        if (isset($data['variations']) && is_array($data['variations'])) {
            // Delete existing variants and create new ones
            ProductVariation::where('product_id', $id)->delete();
            if (!empty($data['variations'])) {
                $this->saveVariants($id, $data['variations'], $data['selected_attributes'] ?? null, true);
            }
        }

        return $this->repository->find($id);
    }

    public function delete(int $id)
    {
        $product = $this->repository->findOrFail($id);
        
        // Delete associated images
        foreach ($product->images as $image) {
            if ($image->image && Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }
            $image->delete();
        }

        // Delete variants
        $product->variations()->delete();

        return $this->repository->delete($id);
    }

    public function updateStatus(int $id, bool $isAvailable)
    {
        $product = $this->repository->findOrFail($id);
        return $product->update(['is_available' => $isAvailable]);
    }

    protected function uploadImages(int $productId, array $images)
    {
        foreach ($images as $image) {
            if ($image && $image->isValid()) {
                $path = $image->store('products', 'public');
                if ($path) {
                    ProductImage::create([
                        'product_id' => $productId,
                        'image' => $path,
                    ]);
                }
            }
        }
    }

    protected function deleteImages(array $imageIds)
    {
        $images = ProductImage::whereIn('id', $imageIds)->get();
        
        foreach ($images as $image) {
            if ($image->image && Storage::disk('public')->exists($image->image)) {
                Storage::disk('public')->delete($image->image);
            }
            $image->delete();
        }
    }

    protected function saveVariants(int $productId, array $variations, $selectedAttributes = null, bool $update = false)
    {
        foreach ($variations as $variation) {
            if (!empty($variation['variation'])) {
                $variantData = [
                    'product_id' => $productId,
                    'variant_name' => $variation['variation'],
                    'price' => isset($variation['price']) && $variation['price'] !== '' ? (float)$variation['price'] : 0,
                    'stock' => isset($variation['stock']) && $variation['stock'] !== '' ? (int)$variation['stock'] : 0,
                    'discount_type' => 'amount',
                    'discount' => 0,
                    'is_available' => true,
                ];

                $variant = ProductVariation::create($variantData);

                // Attach attributes if provided
                if (!empty($selectedAttributes)) {
                    if (is_string($selectedAttributes)) {
                        $attributeIds = array_filter(array_map('intval', explode(',', $selectedAttributes)));
                    } elseif (is_array($selectedAttributes)) {
                        $attributeIds = array_filter(array_map('intval', $selectedAttributes));
                    } else {
                        $attributeIds = [];
                    }
                    
                    if (!empty($attributeIds)) {
                        $variant->attributes()->sync($attributeIds);
                    }
                }
            }
        }
    }
}

