<?php

namespace App\Http\Controllers;

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
        $query = Product::with(['category', 'brand', 'reviews.user', 'images', 'variations.images']);

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
                $q->where('name', 'like', '%' . $searchTerm . '%')
                  ->orWhere('slug', 'like', '%' . $searchTerm . '%');
            });
        }
        if ($request->has('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
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
            $query->orderBy('base_price', 'asc');
        } elseif ($sorting === 'high_to_low') {
            // Sort by price descending (high to low)
            $query->orderBy('base_price', 'desc');
        } elseif ($sorting === 'newest' || $sorting === 'latest') {
            // Sort by creation date descending
            $query->orderBy('created_at', 'desc');
        } elseif ($sorting === 'oldest') {
            // Sort by creation date ascending
            $query->orderBy('created_at', 'asc');
        } else {
            // Default sorting or other valid column names
            $validSortColumns = ['created_at', 'updated_at', 'base_price', 'name', 'stock_quantity'];
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
        $products = Product::where('is_active', true)
            ->with(['category', 'brand', 'reviews.user', 'variations.images'])
            ->get();
        
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
        $product = Product::with(['variations.images', 'reviews.user', 'category', 'brand'])
            ->findOrFail($id);

        // Get similar products
        $similarProducts = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with(['category', 'brand', 'reviews.user', 'variations.images'])
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
        $categories = Category::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['subcategories' => function ($query) {
                $query->where('is_active', true)->with('subcategories');
            }])
            ->get()
            ->map(function ($category) {
                return $this->loadNestedSubcategories($category);
            })
            ->filter(function ($category) {
                return $category !== null;
            })
            ->values();
        return response()->json($categories);
    }

    public function categoryBar()
    {
        $categories = Category::where('is_active', true)
            ->where('show_in_bar', true)
            ->orderBy('priority', 'asc')
            ->get();
        return response()->json($categories);
    }

    public function subcategoryList($id)
    {
        $subcategories = Category::where('is_active', true)
            ->where('parent_id', $id)
            ->with(['subcategories' => function ($query) {
                $query->where('is_active', true)->with('subcategories');
            }])
            ->get()
            ->map(function ($category) {
                return $this->loadNestedSubcategories($category);
            })
            ->filter(function ($category) {
                return $category !== null;
            })
            ->values();
        return response()->json($subcategories);
    }

    /**
     * Recursively load nested subcategories and filter those with no products
     */
    private function loadNestedSubcategories($category)
    {
        // First, recursively process subcategories
        if ($category->subcategories->isNotEmpty()) {
            $filteredSubcategories = $category->subcategories->map(function ($subcategory) {
                return $this->loadNestedSubcategories($subcategory);
            })->filter(function ($sub) {
                return $sub !== null;
            })->values();
            
            $category->setRelation('subcategories', $filteredSubcategories);
        }

        // Check if this category has products or has subcategories that (after filtering) have products
        $hasProducts = Product::where('category_id', $category->id)->where('is_active', true)->exists();
        $hasPopulatedSubcategories = $category->subcategories->isNotEmpty();

        if (!$hasProducts && !$hasPopulatedSubcategories) {
            return null;
        }

        return $category;
    }

    public function brandWithProducts(Request $request)
    {
        $brands = Brand::with(['products' => function ($query) {
            $query->where('is_active', true)
                  ->with(['category', 'brand', 'reviews.user', 'variations.images']);
        }])->get();

        $result = $brands->filter(function ($brand) {
            return $brand->products->isNotEmpty();
        })->map(function ($brand) {
            return [
                'brand' => [
                    'id' => $brand->id,
                    'vendor_id' => $brand->vendor_id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                    'image' => $brand->image_url,
                    'status' => $brand->status,
                    'created_at' => $brand->created_at,
                    'updated_at' => $brand->updated_at,
                ],
                'products' => ProductResource::collection($brand->products)
            ];
        })->values();

        return response()->json($result);
    }

    public function brandWiseProducts($brand_id, Request $request)
    {
        $products = Product::where('brand_id', $brand_id)
            ->where('is_active', true)
            ->with(['category', 'brand', 'reviews.user', 'variations.images'])
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
        $categories = Category::with(['products' => function ($query) {
            $query->where('is_active', true)
                  ->with(['category', 'brand', 'reviews.user', 'images', 'variations.images']);
        }])->get();

        $result = $categories->filter(function ($category) {
            return $category->products->isNotEmpty();
        })->map(function ($category) {
            return [
                'category' => $category,
                'products' => ProductResource::collection($category->products)
            ];
        })->values();

        return response()->json($result);
    }

    public function categoryWiseProducts($category_id, Request $request)
    {
        $allCategoryIds = Category::getAllChildCategoryIds($category_id);

        $products = Product::whereIn('category_id', $allCategoryIds)
            ->with(['category', 'brand', 'reviews.user', 'variations.images'])
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

        $baseQuery = Product::where('is_active', true)
            ->with(['category', 'brand', 'reviews.user', 'variations.images']);

        switch ($productType) {
            case 'newarrival':
                $products = (clone $baseQuery)->orderBy('created_at', 'desc')->get();
                break;

            case 'discounted':
                $products = (clone $baseQuery)
                    ->whereColumn('sale_price', '<', 'base_price')
                    ->where('sale_price', '>', 0)
                    ->orderBy('created_at', 'desc')
                    ->get();
                break;

            case 'top_products':
                $topSelling = OrderItem::select('product_id', DB::raw('sum(quantity) as total_sold'))
                    ->groupBy('product_id')
                    ->orderBy('total_sold', 'desc')
                    ->limit(10)
                    ->pluck('product_id');
                $products = (clone $baseQuery)->whereIn('id', $topSelling)->get();
                break;

            case 'best_selling':
                $products = (clone $baseQuery)
                    ->whereColumn('sale_price', '<', 'base_price')
                    ->where('sale_price', '>', 0)
                    ->orderByRaw('(base_price - sale_price) DESC')
                    ->get();
                break;

            case 'latest':
                $products = (clone $baseQuery)->orderBy('created_at', 'desc')->limit(10)->get();
                break;

            case 'featured':
                $products = (clone $baseQuery)->where('is_featured', true)->orderBy('created_at', 'desc')->get();
                break;

            case 'just_for_you':
                if ($user) {
                    $lastPurchase = OrderItem::whereHas('order', function($q) use ($user) {
                        $q->where('user_id', $user->id);
                    })->with('product')->orderBy('created_at', 'desc')->first();

                    if ($lastPurchase && $lastPurchase->product) {
                        $lastCategory = $lastPurchase->product->category_id;
                        $products = (clone $baseQuery)
                            ->where('category_id', $lastCategory)
                            ->where('id', '!=', $lastPurchase->product_id)
                            ->orderBy('created_at', 'desc')
                            ->get();
                    }
                }
                break;

            case 'featured_deals':
                $products = (clone $baseQuery)
                    ->where('is_deal_of_day', true)
                    ->get();
                break;
            
            default:
                $products = (clone $baseQuery)->orderBy('created_at', 'desc')->get();
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
                'current_page' => (int) $offset,
                'total_pages' => (int) $totalPages,
                'products' => ProductResource::collection($paginated)
            ]
        ]);
    }

    public function favouriteProducts(Request $request)
    {
        $user = $request->user();
        $favourites = FavouriteProduct::where('user_id', $user->id)
            ->with(['product.category', 'product.brand', 'product.reviews.user', 'product.images', 'product.variations.images'])
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
        $favourite->load(['product.category', 'product.brand', 'product.reviews.user', 'product.variations.images']);

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

    public function recommendedProducts(Request $request)
    {
        $user = $request->user();
        $query = Product::where('is_active', true)
            ->with(['category', 'brand', 'reviews.user', 'variations.images']);

        if ($user) {
            // Get user's last orders to find preferred categories and price range
            $lastOrderItems = OrderItem::whereHas('order', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->with('product')->orderBy('created_at', 'desc')->limit(10)->get();

            if ($lastOrderItems->isNotEmpty()) {
                $categoryIds = $lastOrderItems->pluck('product.category_id')->unique()->filter()->toArray();
                $avgPrice = $lastOrderItems->avg('price');

                if (!empty($categoryIds)) {
                    $query->whereIn('category_id', $categoryIds);
                }

                if ($avgPrice > 0) {
                    $query->whereBetween('sale_price', [$avgPrice * 0.7, $avgPrice * 1.3]);
                }
            }
        }

        // Final query: random or latest
        $products = $query->inRandomOrder()->limit(10)->get();

        if ($products->isEmpty()) {
            $products = Product::where('is_active', true)
                ->with(['category', 'brand', 'reviews.user', 'images', 'variations.images'])
                ->inRandomOrder()
                ->limit(10)
                ->get();
        }

        return response()->json([
            'products' => ProductResource::collection($products)
        ]);
    }

    public function dealOfTheDay()
    {
        // For single-vendor, take a popular or latest discounted product
        $deal = Product::where('is_active', true)
            ->where('sale_price', '>', 0)
            ->with(['category', 'brand', 'reviews.user', 'variations.images'])
            ->orderBy('id', 'desc')
            ->first();
        
        if (!$deal) {
            return response()->json(['detail' => 'No deal found.'], 404);
        }

        return response()->json([
            'id' => $deal->id,
            'product' => new ProductResource($deal),
            'created_at' => $deal->created_at
        ]);
    }

    public function notifications()
    {
        $notifications = Notification::orderBy('created_at', 'desc')->get();
        return response()->json($notifications);
    }

}
