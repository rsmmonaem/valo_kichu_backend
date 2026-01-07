<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\FavouriteProduct;
use App\Models\FeaturedProduct;
use App\Models\RecommendedProduct;
use App\Models\Notification;
use App\Models\VendorUser;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CommerceController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes']);

        // Filters
        // Category filter - support both 'category' and 'category_id'
        $categoryId = $request->get('category') ?? $request->get('category_id');
        if ($categoryId && !empty(trim($categoryId))) {
            $query->where('category_id', $categoryId);
        }
        
        // Brand filter - support both 'brand' and 'brand_id'
        $brandId = $request->get('brand') ?? $request->get('brand_id');
        if ($brandId && !empty(trim($brandId))) {
            $query->where('brand_id', $brandId);
        }
        
        // Search filter - support both 'name' and 'search' parameters
        $searchTerm = $request->get('name') ?? $request->get('search');
        if ($searchTerm && !empty(trim($searchTerm))) {
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', '%' . $searchTerm . '%')
                  ->orWhere('product_code', 'like', '%' . $searchTerm . '%');
            });
        }
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }
        if ($request->has('is_new') && $request->is_new) {
            // Filter for new products only - sorting will be handled in sorting section
            // No need to set orderBy here as it conflicts with main sorting
        }
        if ($request->has('is_offered') && $request->is_offered) {
            $query->where(function($q) {
                $q->where('discount_price', '>', 0)
                  ->orWhere('discount', '>', 0);
            });
        }

        // Sorting - support both 'sorting' and 'sort_by' parameters
        $sorting = $request->get('sorting') ?? $request->get('sort_by');
        // If sorting is empty or null, use default
        if (empty($sorting)) {
            $sorting = 'created_at';
        }
        if ($sorting === 'popularity') {
            $query->withCount(['orderItems as popularity' => function($q) {
                $q->select(DB::raw('count(*)'));
            }])->orderBy('popularity', 'desc');
        } elseif ($sorting === 'low_to_high') {
            // Sort by price ascending (low to high)
            $query->orderBy('price', 'asc');
        } elseif ($sorting === 'high_to_low') {
            // Sort by price descending (high to low)
            $query->orderBy('price', 'desc');
        } elseif ($sorting === 'newest' || $sorting === 'latest') {
            // Sort by creation date descending
            $query->orderBy('created_at', 'desc');
        } elseif ($sorting === 'oldest') {
            // Sort by creation date ascending
            $query->orderBy('created_at', 'asc');
        } else {
            // Default sorting or other valid column names
            $validSortColumns = ['created_at', 'updated_at', 'price', 'title', 'discount', 'stock'];
            $sortColumn = in_array($sorting, $validSortColumns) ? $sorting : 'created_at';
            $sortOrder = $request->get('order', 'desc');
            $query->orderBy($sortColumn, $sortOrder);
        }

        // Pagination
        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 1);
        $skip = ($offset - 1) * $limit;

        // Get total count before applying skip/take (clone query to avoid side effects)
        $total = (clone $query)->count();
        
        // Apply pagination
        $products = $query->skip($skip)->take($limit)->get();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'count' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $offset,
            'total_pages' => $totalPages,
            'products' => ProductResource::collection($products)
        ]);
    }

    public function productList(Request $request)
    {
        $products = Product::with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])->get();
        
        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 1);
        $skip = ($offset - 1) * $limit;

        $paginated = $products->skip($skip)->take($limit);
        $total = $products->count();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'count' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $offset,
            'total_pages' => $totalPages,
            'products' => ProductResource::collection($paginated)
        ]);
    }

    public function productDetail($id, Request $request)
    {
        $product = Product::with(['images', 'vendor', 'variants.images', 'variants.attributes', 'reviews.user', 'category', 'brand'])
            ->findOrFail($id);

        // Get similar products
        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
            ->limit(10)
            ->get();

        // Use ProductResource for consistent format
        $productResource = new ProductResource($product);
        $productData = $productResource->toArray($request);
        
        // Add similar_products for product detail
        $productData['similar_products'] = ProductResource::collection($similarProducts);
        
        return response()->json($productData);
    }

    public function brandList()
    {
        $brands = Brand::all()->map(function ($brand) {
            return [
                'id' => $brand->id,
                'vendor_id' => $brand->vendor_id,
                'name' => $brand->name,
                'slug' => $brand->slug,
                'description' => $brand->description,
                'image' => $brand->image_url, // Use accessor for full URL
                'status' => $brand->status,
                'created_at' => $brand->created_at,
                'updated_at' => $brand->updated_at,
            ];
        });
        return response()->json($brands);
    }

    public function categoryList()
    {
        $categories = Category::whereNull('parent_id')
            ->with(['subcategories' => function ($query) {
                $query->with('subcategories');
            }])
            ->get()
            ->map(function ($category) {
                return $this->loadNestedSubcategories($category);
            });
        return response()->json($categories);
    }

    public function subcategoryList($id)
    {
        $subcategories = Category::where('parent_id', $id)->with(['subcategories' => function ($query) {
            $query->with('subcategories');
        }])->get()->map(function ($category) {
            return $this->loadNestedSubcategories($category);
        });
        return response()->json($subcategories);
    }

    /**
     * Recursively load nested subcategories
     */
    private function loadNestedSubcategories($category)
    {
        $category->load('subcategories');
        
        if ($category->subcategories->isNotEmpty()) {
            $category->subcategories = $category->subcategories->map(function ($subcategory) {
                return $this->loadNestedSubcategories($subcategory);
            });
        }
        
        return $category;
    }

    public function brandWithProducts(Request $request)
    {
        $brands = Brand::all();
        $result = [];

        foreach ($brands as $brand) {
            $products = Product::where('brand_id', $brand->id)
                ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                ->get();
            if ($products->isEmpty()) {
                continue;
            }

            $result[] = [
                'brand' => [
                    'id' => $brand->id,
                    'vendor_id' => $brand->vendor_id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                    'image' => $brand->image_url, // Use accessor for full URL
                    'status' => $brand->status,
                    'created_at' => $brand->created_at,
                    'updated_at' => $brand->updated_at,
                ],
                'products' => ProductResource::collection($products)
            ];
        }

        return response()->json($result);
    }

    public function brandWiseProducts($brand_id, Request $request)
    {
        $products = Product::where('brand_id', $brand_id)
            ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
            ->get();
        
        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products were found for this brand.'], 404);
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 1);
        $skip = ($offset - 1) * $limit;

        $paginated = $products->skip($skip)->take($limit);
        $total = $products->count();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'count' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $offset,
            'total_pages' => $totalPages,
            'products' => ProductResource::collection($paginated)
        ]);
    }

    public function categoriesWithProducts(Request $request)
    {
        $categories = Category::all();
        $result = [];

        foreach ($categories as $category) {
            $products = Product::where('category_id', $category->id)
                ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                ->get();
            if ($products->isEmpty()) {
                continue;
            }

            $result[] = [
                'category' => $category,
                'products' => ProductResource::collection($products)
            ];
        }

        return response()->json($result);
    }

    public function categoryWiseProducts($category_id, Request $request)
    {
        $allCategoryIds = Category::getAllChildCategoryIds($category_id);

        $products = Product::whereIn('category_id', $allCategoryIds)
            ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
            ->get();

        if ($products->isEmpty()) {
            return response()->json(['message' => 'No products were found for this category.'], 404);
        }


        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 1);
        $skip = ($offset - 1) * $limit;

        $paginated = $products->skip($skip)->take($limit);
        $total = $products->count();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'count' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $offset,
            'total_pages' => $totalPages,
            'products' => ProductResource::collection($paginated)
        ]);
    }

    public function productSections(Request $request)
    {
        $productType = $request->get('type', 'all');
        $user = $request->user();
        $products = collect();

        switch ($productType) {
            case 'newarrival':
                $products = Product::where('is_available', true)
                    ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                    ->orderBy('created_at', 'desc')
                    ->get();
                break;

            case 'discounted':
                $products = Product::where('discount', '>', 0)
                    ->where('is_available', true)
                    ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                    ->orderBy('created_at', 'desc')
                    ->get();
                break;

            case 'top_products':
                $topSelling = OrderItem::select('product_id', DB::raw('sum(quantity) as total_sold'))
                    ->groupBy('product_id')
                    ->orderBy('total_sold', 'desc')
                    ->limit(10)
                    ->pluck('product_id');
                $products = Product::whereIn('id', $topSelling)
                    ->where('is_available', true)
                    ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                    ->get();
                break;

            case 'best_selling':
                $products = Product::where('discount', '>', 0)
                    ->where('is_available', true)
                    ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                    ->orderBy('discount', 'desc')
                    ->get();
                break;

            case 'latest':
                $products = Product::where('discount', '>', 0)
                    ->where('is_available', true)
                    ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();
                break;

            case 'featured':
                $featured = FeaturedProduct::with(['product.images', 'product.category', 'product.brand', 'product.vendor', 'product.reviews.user', 'product.variants.images', 'product.variants.attributes'])
                    ->whereHas('product', function($q) {
                        $q->where('is_available', true);
                    })
                    ->orderBy('created_at', 'desc')
                    ->get();
                $products = $featured->pluck('product');
                break;

            case 'just_for_you':
                if ($user) {
                    $lastPurchase = OrderItem::whereHas('order', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->orderBy('created_at', 'desc')->first();

                    if ($lastPurchase) {
                        $lastCategory = $lastPurchase->product->category_id;
                        $products = Product::where('category_id', $lastCategory)
                            ->where('is_available', true)
                            ->where('id', '!=', $lastPurchase->product_id)
                            ->with(['images', 'category', 'brand', 'vendor', 'reviews.user', 'variants.images', 'variants.attributes'])
                            ->orderBy('created_at', 'desc')
                            ->get();
                    }
                }
                break;

            case 'featured_deals':
                $products = Product::where('is_available', true)
                    ->with(['images', 'category', 'brand', 'vendor', 'orderItems', 'reviews.user', 'variants.images', 'variants.attributes'])
                    ->get()
                    ->map(function($product) {
                        $product->total_sold = $product->orderItems->sum('quantity');
                        $product->reviews_avg_rating = $product->reviews->avg('rating') ?? 0;
                        return $product;
                    })
                    ->filter(function($product) {
                        return $product->total_sold > 0 && $product->reviews_avg_rating >= 4.0;
                    })
                    ->sortByDesc('total_sold')
                    ->values();
                break;
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 1);
        $skip = ($offset - 1) * $limit;

        $paginated = $products->skip($skip)->take($limit);
        $total = $products->count();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'type' => $productType,
            'results' => [
                'count' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'current_page' => $offset,
                'total_pages' => $totalPages,
                'products' => ProductResource::collection($paginated)
            ]
        ]);
    }

    public function favouriteProducts(Request $request)
    {
        $user = $request->user();
        $favourites = FavouriteProduct::where('user_id', $user->id)
            ->with(['product.images', 'product.category', 'product.brand', 'product.vendor', 'product.reviews.user', 'product.variants.images', 'product.variants.attributes'])
            ->get();

        $formattedFavourites = $favourites->map(function ($favourite) {
            return [
                'id' => $favourite->id,
                'user_id' => $favourite->user_id,
                'product_id' => $favourite->product_id,
                'product' => $favourite->product ? new ProductResource($favourite->product) : null,
                'created_at' => $favourite->created_at?->toDateTimeString(),
                'updated_at' => $favourite->updated_at?->toDateTimeString(),
            ];
        });

        return response()->json($formattedFavourites);
    }

    public function addFavourite($item_id, Request $request)
    {
        $user = $request->user();
        $product = Product::findOrFail($item_id);

        if (FavouriteProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->exists()) {
            return response()->json(['message' => 'Product already in favourites'], 400);
        }

        $favourite = FavouriteProduct::create([
            'user_id' => $user->id,
            'product_id' => $product->id
        ]);

        // Load the product relationship with all necessary data
        $favourite->load(['product.images', 'product.category', 'product.brand', 'product.vendor', 'product.reviews.user', 'product.variants.images', 'product.variants.attributes']);

        return response()->json([
            'message' => 'Product added to favourites',
            'favourite' => [
                'id' => $favourite->id,
                'user_id' => $favourite->user_id,
                'product_id' => $favourite->product_id,
                'product' => new ProductResource($favourite->product),
                'created_at' => $favourite->created_at?->toDateTimeString(),
                'updated_at' => $favourite->updated_at?->toDateTimeString(),
            ]
        ], 201);
    }

    public function removeFavourite($item_id, Request $request)
    {
        $user = $request->user();
        $product = Product::findOrFail($item_id);

        $favourite = FavouriteProduct::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if (!$favourite) {
            return response()->json(['message' => 'Item not in favourites'], 400);
        }

        $favourite->delete();
        return response()->json(['message' => 'Item removed from favourites'], 200);
    }

    public function recommendedProducts()
    {
        $recommended = RecommendedProduct::orderBy('id', 'desc')->first();
        
        if (!$recommended) {
            return response()->json(['detail' => 'No recommended product found.'], 404);
        }

        return response()->json([
            'id' => $recommended->id,
            'product' => new ProductResource($recommended->product),
            'created_at' => $recommended->created_at
        ]);
    }

    public function notifications()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();
        return response()->json($notifications);
    }

    public function vendorList()
    {
        $vendors = VendorUser::all();
        return response()->json($vendors);
    }

    public function vendorDetail($id)
    {
        $vendor = VendorUser::findOrFail($id);
        
        // Calculate stats
        $totalSold = OrderItem::whereHas('product', function($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id);
        })->sum('quantity');

        $avgRating = $vendor->vendorReviews()->avg('rating');
        $ratingCount = $vendor->vendorReviews()->count();
        $totalProduct = $vendor->products()->count();
        $totalReview = $vendor->vendorReviews()->count();

        return response()->json([
            'id' => $vendor->id,
            'email' => $vendor->email,
            'first_name' => $vendor->first_name,
            'last_name' => $vendor->last_name,
            'phone_number' => $vendor->phone_number,
            'profile_picture' => $vendor->profile_picture,
            'total_sold' => $totalSold,
            'average_rating' => round($avgRating, 2),
            'rating_count' => $ratingCount,
            'total_product' => $totalProduct,
            'total_review' => $totalReview,
            'shop' => [
                'shop_name' => $vendor->shop_name,
                'shop_address' => $vendor->shop_address,
                'shop_contact_number' => $vendor->shop_contact_number,
                'shop_image' => $vendor->shop_image,
                'shop_banner' => $vendor->shop_banner,
            ]
        ]);
    }

    public function topVendors()
    {
        $topVendors = VendorUser::withCount(['products as total_sold' => function($q) {
            $q->select(DB::raw('sum(order_items.quantity)'))
              ->join('order_items', 'products.id', '=', 'order_items.product_id');
        }])
        ->orderBy('total_sold', 'desc')
        ->limit(10)
        ->get();

        return response()->json($topVendors);
    }

    public function vendorProducts($id, Request $request)
    {
        $vendor = VendorUser::findOrFail($id);
        $products = Product::where('vendor_id', $id);

        if ($request->has('category_id')) {
            $products->where('category_id', $request->category_id);
        }
        if ($request->has('brand_id')) {
            $products->where('brand_id', $request->brand_id);
        }
        if ($request->has('search')) {
            $products->where('title', 'like', '%' . $request->search . '%');
        }

        $products = $products->orderBy('created_at', 'desc')->get();

        if ($products->isEmpty()) {
            return response()->json(['error' => 'Products not found for this vendor'], 404);
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 1);
        $skip = ($offset - 1) * $limit;

        $paginated = $products->skip($skip)->take($limit);
        $total = $products->count();
        $totalPages = ceil($total / $limit);

        return response()->json([
            'count' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'current_page' => $offset,
            'total_pages' => $totalPages,
            'products' => ProductResource::collection($paginated)
        ]);
    }

    public function vendorBrands($id)
    {
        $vendorProducts = Product::where('vendor_id', $id)->pluck('brand_id')->unique();
        $brands = Brand::whereIn('id', $vendorProducts)->get();

        if ($brands->isEmpty()) {
            return response()->json(['error' => 'No brands found for this vendor'], 404);
        }

        return response()->json($brands);
    }

    public function vendorCategories($id)
    {
        $vendorProducts = Product::where('vendor_id', $id)->pluck('category_id')->unique();
        $categories = Category::whereIn('id', $vendorProducts)->get();

        if ($categories->isEmpty()) {
            return response()->json(['error' => 'No categories found for this vendor'], 404);
        }

        return response()->json($categories);
    }
}
