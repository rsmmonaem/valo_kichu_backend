<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\MohasagorController;
use App\Http\Controllers\MohasagorController1;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;





/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/



// temporary route to seed categories
// Route::get('/admin/seed-categories', function () {

//     $now = now();

//     // 1️⃣ Parent categories
//     $parentCategories = [
//         "mens-boys-fashion" => "Men's & Boys' Fashion",
//         "womens-girls-fashion" => "Women’s & Girls' Fashion",
//         "kids-fashion" => "Kids' Fashion",
//         "mother-baby" => "Mother & Baby",
//         "health-beauty" => "Health & Beauty",
//         "home-appliances" => "Home Appliances",
//         "kitchen-appliances" => "Kitchen Appliances",
//         "electronics-gadget" => "Electronics Device & Gadget",
//         "watches-bags-jewellery" => "Watches, Bags, Jewellery",
//         "sports-outdoors" => "Sports & Outdoors",
//         "automotive-motorbike" => "Automotive & Motorbike",
//     ];

//     $parentIds = [];
//     foreach ($parentCategories as $slug => $name) {
//         DB::table('categories')->updateOrInsert(
//             ['slug' => $slug],
//             [
//                 'name' => $name,
//                 'parent_id' => null,
//                 'is_active' => 1,
//                 'priority' => 1,
//                 'created_at' => $now,
//                 'updated_at' => $now
//             ]
//         );

//         $parentIds[$slug] = DB::table('categories')->where('slug', $slug)->value('id');
//     }

//     // 2️⃣ Child categories
//     $childCategories = [
//         // Men’s subcategories
//         ['name'=>"Men's Clothing",'slug'=>'mens-clothing','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Men's Hoodies & Sweatshirts",'slug'=>'mens-hoodies-sweatshirts','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Men's Jeans",'slug'=>'mens-jeans','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Men's T-Shirts",'slug'=>'mens-tshirts','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Polo Shirts",'slug'=>'polo-shirts','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Casual Shirts",'slug'=>'casual-shirts','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Formal Shirts",'slug'=>'formal-shirts','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Joggers & Sweat Pants",'slug'=>'joggers-sweat-pants','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Blazers",'slug'=>'blazers','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Jackets",'slug'=>'jackets','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Men's Shoes",'slug'=>'mens-shoes','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Men's Accessories",'slug'=>'mens-accessories','parent_slug'=>'mens-boys-fashion'],
//         ['name'=>"Men's Muslim Wear",'slug'=>'mens-muslim-wear','parent_slug'=>'mens-boys-fashion'],

//         // Women’s subcategories
//         ['name'=>"Women’s Traditional Wear",'slug'=>'womens-traditional-wear','parent_slug'=>'womens-girls-fashion'],
//         ['name'=>"Women’s Western Wear",'slug'=>'womens-western-wear','parent_slug'=>'womens-girls-fashion'],
//         ['name'=>"Women’s Muslim Wear",'slug'=>'womens-muslim-wear','parent_slug'=>'womens-girls-fashion'],
//         ['name'=>"Women’s Innerwear",'slug'=>'womens-innerwear','parent_slug'=>'womens-girls-fashion'],
//         ['name'=>"Women’s Shoes",'slug'=>'womens-shoes','parent_slug'=>'womens-girls-fashion'],
//         ['name'=>"Women’s Bags",'slug'=>'womens-bags','parent_slug'=>'womens-girls-fashion'],
//         ['name'=>"Women’s Accessories",'slug'=>'womens-accessories','parent_slug'=>'womens-girls-fashion'],

//         // Kids & Baby
//         ['name'=>"Boys' Fashion",'slug'=>'boys-fashion','parent_slug'=>'kids-fashion'],
//         ['name'=>"Girls' Fashion",'slug'=>'girls-fashion','parent_slug'=>'kids-fashion'],
//         ['name'=>"Newborn Fashion",'slug'=>'newborn-fashion','parent_slug'=>'kids-fashion'],
//         ['name'=>"Baby Toys",'slug'=>'baby-toys','parent_slug'=>'mother-baby'],
//         ['name'=>"Baby Feeding",'slug'=>'baby-feeding','parent_slug'=>'mother-baby'],
//         ['name'=>"Baby Diapers",'slug'=>'baby-diapers','parent_slug'=>'mother-baby'],
//         ['name'=>"Baby Skin Care",'slug'=>'baby-skin-care','parent_slug'=>'mother-baby'],
//         ['name'=>"Baby Bedding",'slug'=>'baby-bedding','parent_slug'=>'mother-baby'],

//         // Electronics
//         ['name'=>"Smart Watch",'slug'=>'smart-watch','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Wireless Earbuds (TWS)",'slug'=>'wireless-earbuds','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Bluetooth Headphones",'slug'=>'bluetooth-headphones','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Portable Bluetooth Speaker",'slug'=>'portable-bluetooth-speaker','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Smartphone",'slug'=>'smartphone','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Laptops",'slug'=>'laptops','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Television",'slug'=>'television','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Camera & DSLR",'slug'=>'camera-dslr','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Routers",'slug'=>'routers','parent_slug'=>'electronics-gadget'],
//         ['name'=>"Power Bank",'slug'=>'power-bank','parent_slug'=>'electronics-gadget'],
//     ];

//     foreach ($childCategories as $child) {
//         DB::table('categories')->updateOrInsert(
//             ['slug' => $child['slug']],
//             [
//                 'name' => $child['name'],
//                 'parent_id' => $parentIds[$child['parent_slug']] ?? null,
//                 'is_active' => 1,
//                 'priority' => 1,
//                 'created_at' => $now,
//                 'updated_at' => $now
//             ]
//         );
//     }

//     return response()->json([
//         'status' => true,
//         'message' => 'All categories seeded successfully!',
//         'parents_inserted' => count($parentCategories),
//         'children_inserted' => count($childCategories)
//     ]);

// }); // <-- No auth middleware for testing
Route::get('/admin/seed-categories', function () {

    $now = now();

    // 1️⃣ Parent categories
    $parentCategories = [
        "mens-boys-fashion" => "Men's & Boys' Fashion",
        "womens-girls-fashion" => "Women’s & Girls' Fashion",
        "kids-fashion" => "Kids' Fashion",
        "mother-baby" => "Mother & Baby",
        "health-beauty" => "Health & Beauty",
        "home-appliances" => "Home Appliances",
        "kitchen-appliances" => "Kitchen Appliances",
        "electronics-gadget" => "Electronics Device & Gadget",
        "electronics-accessories" => "Electronics Accessories",
        "watches-bags-jewellery" => "Watches, Bags, Jewellery",
        "sports-outdoors" => "Sports & Outdoors",
        "automotive-motorbike" => "Automotive & Motorbike",
    ];

    $parentIds = [];
    foreach ($parentCategories as $slug => $name) {
        DB::table('categories')->updateOrInsert(
            ['slug' => $slug],
            [
                'name' => $name,
                'parent_id' => null,
                'is_active' => 1,
                'priority' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]
        );

        $parentIds[$slug] = DB::table('categories')->where('slug', $slug)->value('id');
    }

    // 2️⃣ Child and subcategories
    $childCategories = [

        // Men’s & Boys’ Fashion
        ['name' => "Men's Clothing", 'slug' => 'mens-clothing', 'parent_slug' => 'mens-boys-fashion'],
        ['name' => "Men's T-Shirts", 'slug' => 'mens-tshirts', 'parent_slug' => 'mens-clothing'],
        ['name' => "Formal Shirts", 'slug' => 'formal-shirts', 'parent_slug' => 'mens-clothing'],
        ['name' => "Casual Shirts", 'slug' => 'casual-shirts', 'parent_slug' => 'mens-clothing'],
        ['name' => "Polo Shirts", 'slug' => 'polo-shirts', 'parent_slug' => 'mens-clothing'],
        ['name' => "Men's Clubs Jerseys", 'slug' => 'mens-clubs-jerseys', 'parent_slug' => 'mens-clothing'],
        ['name' => "Katua/Fatua", 'slug' => 'katua-fatua', 'parent_slug' => 'mens-clothing'],
        ['name' => "Blazers", 'slug' => 'blazers', 'parent_slug' => 'mens-clothing'],
        ['name' => "Coats", 'slug' => 'coats', 'parent_slug' => 'mens-clothing'],
        ['name' => "Jackets", 'slug' => 'jackets', 'parent_slug' => 'mens-clothing'],
        ['name' => "Nightwear", 'slug' => 'nightwear', 'parent_slug' => 'mens-clothing'],
        ['name' => "Shawls", 'slug' => 'shawls', 'parent_slug' => 'mens-clothing'],
        ['name' => "Hoodies & Sweatshirts", 'slug' => 'hoodies-sweatshirts', 'parent_slug' => 'mens-clothing'],
        ['name' => "Joggers & Sweat Pants", 'slug' => 'joggers-sweat-pants', 'parent_slug' => 'mens-clothing'],
        ['name' => "Men's Jeans", 'slug' => 'mens-jeans', 'parent_slug' => 'mens-clothing'],
        ['name' => "Gabardines and Chinos", 'slug' => 'gabardines-chinos', 'parent_slug' => 'mens-clothing'],
        ['name' => "Lungi", 'slug' => 'lungi', 'parent_slug' => 'mens-clothing'],
        ['name' => "Trunks & Boxers", 'slug' => 'trunks-boxers', 'parent_slug' => 'mens-clothing'],
        ['name' => "Socks & Tights", 'slug' => 'socks-tights', 'parent_slug' => 'mens-clothing'],
        ['name' => "Men's Muslim Wear", 'slug' => 'mens-muslim-wear', 'parent_slug' => 'mens-boys-fashion'],
        ['name' => "Men's Shoes", 'slug' => 'mens-shoes', 'parent_slug' => 'mens-boys-fashion'],
        ['name' => "Men's Accessories", 'slug' => 'mens-accessories', 'parent_slug' => 'mens-boys-fashion'],

        // Women’s & Girls’ Fashion
        ['name' => "Women’s Traditional Wear", 'slug' => 'womens-traditional-wear', 'parent_slug' => 'womens-girls-fashion'],
        ['name' => "Women’s Western Wear", 'slug' => 'womens-western-wear', 'parent_slug' => 'womens-girls-fashion'],
        ['name' => "Women’s Muslim Wear", 'slug' => 'womens-muslim-wear', 'parent_slug' => 'womens-girls-fashion'],
        ['name' => "Women’s Innerwear", 'slug' => 'womens-innerwear', 'parent_slug' => 'womens-girls-fashion'],
        ['name' => "Women’s Shoes", 'slug' => 'womens-shoes', 'parent_slug' => 'womens-girls-fashion'],
        ['name' => "Women’s Bags", 'slug' => 'womens-bags', 'parent_slug' => 'womens-girls-fashion'],
        ['name' => "Women’s Accessories", 'slug' => 'womens-accessories', 'parent_slug' => 'womens-girls-fashion'],

        // Kids’ Fashion
        ['name' => "Boys’ Fashion", 'slug' => 'boys-fashion', 'parent_slug' => 'kids-fashion'],
        ['name' => "Girls’ Fashion", 'slug' => 'girls-fashion', 'parent_slug' => 'kids-fashion'],
        ['name' => "Newborn Fashion", 'slug' => 'newborn-fashion', 'parent_slug' => 'kids-fashion'],
        ['name' => "Cute Baby Clothing", 'slug' => 'cute-baby-clothing', 'parent_slug' => 'kids-fashion'],

        // Mother & Baby
        ['name' => "Baby Gift Set", 'slug' => 'baby-gift-set', 'parent_slug' => 'mother-baby'],
        ['name' => "Newborn Combo", 'slug' => 'newborn-combo', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Bouncer", 'slug' => 'baby-bouncer', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Foods", 'slug' => 'baby-foods', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Feeding", 'slug' => 'baby-feeding', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Diapers", 'slug' => 'baby-diapers', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Nursing", 'slug' => 'baby-nursing', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Skin Care", 'slug' => 'baby-skin-care', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Bedding", 'slug' => 'baby-bedding', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Toys", 'slug' => 'baby-toys', 'parent_slug' => 'mother-baby'],
        ['name' => "Kids Study Table", 'slug' => 'kids-study-table', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Care Items", 'slug' => 'baby-care-items', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Clothes", 'slug' => 'baby-clothes', 'parent_slug' => 'mother-baby'],
        ['name' => "Baby Winter Suits", 'slug' => 'baby-winter-suits', 'parent_slug' => 'mother-baby'],
        ['name' => "Breastfeeding", 'slug' => 'breastfeeding', 'parent_slug' => 'mother-baby'],

        // Health & Beauty
        ['name' => "Personal Care", 'slug' => 'personal-care', 'parent_slug' => 'health-beauty'],
        ['name' => "Beauty Care", 'slug' => 'beauty-care', 'parent_slug' => 'health-beauty'],
        ['name' => "Hair Care", 'slug' => 'hair-care', 'parent_slug' => 'health-beauty'],
        ['name' => "Skin Care", 'slug' => 'skin-care', 'parent_slug' => 'health-beauty'],
        ['name' => "Soap", 'slug' => 'soap', 'parent_slug' => 'skin-care'],
        ['name' => "Body Care", 'slug' => 'body-care', 'parent_slug' => 'health-beauty'],
        ['name' => "Massagers", 'slug' => 'massagers', 'parent_slug' => 'body-care'],
        ['name' => "Beauty Tools", 'slug' => 'beauty-tools', 'parent_slug' => 'health-beauty'],
        ['name' => "Trimmer", 'slug' => 'trimmer', 'parent_slug' => 'health-beauty'],
        ['name' => "Fragrances", 'slug' => 'fragrances', 'parent_slug' => 'health-beauty'],
        ['name' => "Surgical Item", 'slug' => 'surgical-item', 'parent_slug' => 'health-beauty'],

        // Home Appliances
        ['name' => "Furniture & Decor", 'slug' => 'furniture-decor', 'parent_slug' => 'home-appliances'],
        ['name' => "Beautiful Your Home", 'slug' => 'beautiful-home', 'parent_slug' => 'furniture-decor'],
        ['name' => "Home Watch", 'slug' => 'home-watch', 'parent_slug' => 'furniture-decor'],
        ['name' => "Router Stand", 'slug' => 'router-stand', 'parent_slug' => 'furniture-decor'],
        ['name' => "Hangers", 'slug' => 'hangers', 'parent_slug' => 'furniture-decor'],
        ['name' => "Stool Chair & Accessories", 'slug' => 'stool-chair', 'parent_slug' => 'furniture-decor'],
        ['name' => "Clothes Line & Shoes Racks", 'slug' => 'clothes-line', 'parent_slug' => 'furniture-decor'],
        ['name' => "Mosquito Killer Tools", 'slug' => 'mosquito-killer', 'parent_slug' => 'furniture-decor'],
        ['name' => "Studying Table & Stand", 'slug' => 'studying-table', 'parent_slug' => 'furniture-decor'],
        ['name' => "Smoking Ashtray", 'slug' => 'smoking-ashtray', 'parent_slug' => 'furniture-decor'],
        ['name' => "Storage & Organization", 'slug' => 'storage-organization', 'parent_slug' => 'furniture-decor'],

        ['name' => "Cooling & Heating", 'slug' => 'cooling-heating', 'parent_slug' => 'home-appliances'],
        ['name' => "Water Heaters", 'slug' => 'water-heaters', 'parent_slug' => 'cooling-heating'],
        ['name' => "Room Heater", 'slug' => 'room-heater', 'parent_slug' => 'cooling-heating'],
        ['name' => "Air Coolers", 'slug' => 'air-coolers', 'parent_slug' => 'cooling-heating'],

        ['name' => "Bedding & Bath", 'slug' => 'bedding-bath', 'parent_slug' => 'home-appliances'],
        ['name' => "Lighting", 'slug' => 'lighting', 'parent_slug' => 'home-appliances'],
        ['name' => "Fans", 'slug' => 'fans', 'parent_slug' => 'home-appliances'],
        ['name' => "Television", 'slug' => 'television-home', 'parent_slug' => 'home-appliances'],
        ['name' => "Refrigerators", 'slug' => 'refrigerators', 'parent_slug' => 'home-appliances'],
        ['name' => "Air Conditioners", 'slug' => 'air-conditioners', 'parent_slug' => 'home-appliances'],
        ['name' => "Washing Machines", 'slug' => 'washing-machines', 'parent_slug' => 'home-appliances'],
        ['name' => "Irons & Garment Steamers", 'slug' => 'irons-garment', 'parent_slug' => 'home-appliances'],
        ['name' => "Washers & Dryers", 'slug' => 'washers-dryers', 'parent_slug' => 'home-appliances'],
        ['name' => "Laundry & Cleaning", 'slug' => 'laundry-cleaning', 'parent_slug' => 'home-appliances'],
        ['name' => "Vacuums & Floor Care", 'slug' => 'vacuums-floor', 'parent_slug' => 'home-appliances'],
        ['name' => "Stationery & Craft", 'slug' => 'stationery-craft', 'parent_slug' => 'home-appliances'],
        ['name' => "Tools, DIY & Outdoor", 'slug' => 'tools-diy', 'parent_slug' => 'home-appliances'],
        ['name' => "Home Accessories", 'slug' => 'home-accessories', 'parent_slug' => 'home-appliances'],

        // Continue same style for Kitchen Appliances, Electronics Device & Gadget, Electronics Accessories,
        // Watches, Bags, Jewellery, Sports & Outdoors, Automotive & Motorbike
        // You can copy-paste from your text list following this same pattern

    ];
    // Kitchen Appliances
    $childCategories = array_merge($childCategories, [
        ['name' => "Small Appliances", 'slug' => 'small-appliances', 'parent_slug' => 'kitchen-appliances'],
        ['name' => "Food Processors", 'slug' => 'food-processors', 'parent_slug' => 'small-appliances'],
        ['name' => "Ice Makers", 'slug' => 'ice-makers', 'parent_slug' => 'small-appliances'],
        ['name' => "Egg Master", 'slug' => 'egg-master', 'parent_slug' => 'small-appliances'],
        ['name' => "Yogurt Makers", 'slug' => 'yogurt-makers', 'parent_slug' => 'small-appliances'],
        ['name' => "Breakfast Makers", 'slug' => 'breakfast-makers', 'parent_slug' => 'small-appliances'],
        ['name' => "Oil Sprayers & Dispensers", 'slug' => 'oil-sprayers', 'parent_slug' => 'small-appliances'],
        ['name' => "Knife Sharpeners", 'slug' => 'knife-sharpeners', 'parent_slug' => 'small-appliances'],
        ['name' => "Lunch Bags & Boxes", 'slug' => 'lunch-bags', 'parent_slug' => 'small-appliances'],
        ['name' => "Water Purifiers", 'slug' => 'water-purifiers', 'parent_slug' => 'small-appliances'],
        ['name' => "Water Dispensers", 'slug' => 'water-dispensers', 'parent_slug' => 'small-appliances'],
        ['name' => "Kitchen & Table Linen", 'slug' => 'kitchen-table-linen', 'parent_slug' => 'small-appliances'],
        ['name' => "Kitchen Apron", 'slug' => 'kitchen-apron', 'parent_slug' => 'small-appliances'],
        ['name' => "Hand Gloves", 'slug' => 'hand-gloves', 'parent_slug' => 'small-appliances'],
        ['name' => "Meat & Poultry Tools", 'slug' => 'meat-poultry-tools', 'parent_slug' => 'small-appliances'],
        ['name' => "Heaters", 'slug' => 'heaters', 'parent_slug' => 'small-appliances'],

        ['name' => "Kitchen Accessories", 'slug' => 'kitchen-accessories', 'parent_slug' => 'kitchen-appliances'],
        ['name' => "Cookware", 'slug' => 'cookware', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Rice Cookers", 'slug' => 'rice-cookers', 'parent_slug' => 'cookware'],
        ['name' => "Infrared Cooker", 'slug' => 'infrared-cooker', 'parent_slug' => 'cookware'],
        ['name' => "Stoves & Hot Plates", 'slug' => 'stoves-hot-plates', 'parent_slug' => 'cookware'],
        ['name' => "Bakeware", 'slug' => 'bakeware', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Microwaves", 'slug' => 'microwaves', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Drinkware", 'slug' => 'drinkware', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Dinnerware", 'slug' => 'dinnerware', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Coffee Makers", 'slug' => 'coffee-makers', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Electric Kettles", 'slug' => 'electric-kettles', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Tea Flask", 'slug' => 'tea-flask', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Blenders", 'slug' => 'blenders', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Grinders", 'slug' => 'grinders', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Hand Mixers", 'slug' => 'hand-mixers', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Air Fryers", 'slug' => 'air-fryers', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Kitchen Storage", 'slug' => 'kitchen-storage', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Kitchen Rack", 'slug' => 'kitchen-rack', 'parent_slug' => 'kitchen-accessories'],
        ['name' => "Kitchen Utensils", 'slug' => 'kitchen-utensils', 'parent_slug' => 'kitchen-accessories'],
    ]);

    // Electronics Device & Gadget (remaining)
    $childCategories = array_merge($childCategories, [
        ['name' => "Gadget", 'slug' => 'gadget', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Smart Bluetooth Calling Watch", 'slug' => 'smart-bluetooth-calling-watch', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Wireless Microphone", 'slug' => 'wireless-microphone', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Gas Lighter", 'slug' => 'gas-lighter', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Torch Light", 'slug' => 'torch-light', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Smart Bracelet / Fitness Band", 'slug' => 'smart-bracelet', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Feature Phone", 'slug' => 'feature-phone', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Tablets", 'slug' => 'tablets', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Camera", 'slug' => 'camera', 'parent_slug' => 'electronics-gadget'],
        ['name' => "DSLR", 'slug' => 'dslr', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Drones", 'slug' => 'drones', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Smart Security Camera", 'slug' => 'smart-security-camera', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Desktops Computers", 'slug' => 'desktops', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Scanners", 'slug' => 'scanners', 'parent_slug' => 'electronics-gadget'],
        ['name' => "Soundbar", 'slug' => 'soundbar', 'parent_slug' => 'electronics-gadget'],
    ]);

    // Electronics Accessories
    $childCategories = array_merge($childCategories, [
        ['name' => "Storage", 'slug' => 'storage', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Fast Charger", 'slug' => 'fast-charger', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Power Bank", 'slug' => 'power-bank-accessory', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Phone Holder / Stand", 'slug' => 'phone-holder', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Gadgets Accessories", 'slug' => 'gadgets-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Smart Watch Accessories", 'slug' => 'smartwatch-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Mobile Accessories", 'slug' => 'mobile-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Camera Accessories", 'slug' => 'camera-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Drone Accessories", 'slug' => 'drone-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Sports & Action Camera Accessories", 'slug' => 'sports-camera-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Computer Accessories", 'slug' => 'computer-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Mac Accessories", 'slug' => 'mac-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Printers & Accessories", 'slug' => 'printers-accessories', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Network Components", 'slug' => 'network-components', 'parent_slug' => 'electronics-accessories'],
        ['name' => "Lighting & Studio Equipment", 'slug' => 'lighting-studio', 'parent_slug' => 'electronics-accessories'],
    ]);

    // Watches, Bags, Jewellery
    $childCategories = array_merge($childCategories, [
        ['name' => "Home Watch", 'slug' => 'home-watch', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Umbrellas", 'slug' => 'umbrellas', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Kids’ Umbrellas & Rainwear", 'slug' => 'kids-umbrellas', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Rain Coats", 'slug' => 'rain-coats', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Smart Watches", 'slug' => 'smart-watches', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Boys Watches", 'slug' => 'boys-watches', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Girls Watches", 'slug' => 'girls-watches', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Watches For Kids", 'slug' => 'watches-kids', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Jewellery For Women", 'slug' => 'jewellery-women', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Jewellery For Men", 'slug' => 'jewellery-men', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Men’s Bags", 'slug' => 'mens-bags', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Women’s Bags", 'slug' => 'womens-bags-wj', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Travel Bag", 'slug' => 'travel-bag', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Laptop Backpacks", 'slug' => 'laptop-backpacks', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Backpacks", 'slug' => 'backpacks', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Kids Bags", 'slug' => 'kids-bags', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Trolley Backpack", 'slug' => 'trolley-backpack', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Bags and Travel Accessories", 'slug' => 'bags-travel-accessories', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Men’s Sunglasses", 'slug' => 'mens-sunglasses', 'parent_slug' => 'watches-bags-jewellery'],
        ['name' => "Women’s Sunglasses", 'slug' => 'womens-sunglasses', 'parent_slug' => 'watches-bags-jewellery'],
    ]);

    // Sports & Outdoors
    $childCategories = array_merge($childCategories, [
        ['name' => "Sports", 'slug' => 'sports', 'parent_slug' => 'sports-outdoors'],
        ['name' => "Balls", 'slug' => 'balls', 'parent_slug' => 'sports'],
        ['name' => "Cricket", 'slug' => 'cricket', 'parent_slug' => 'sports'],
        ['name' => "Football", 'slug' => 'football', 'parent_slug' => 'sports'],
        ['name' => "Basketball", 'slug' => 'basketball', 'parent_slug' => 'sports'],
        ['name' => "Baseball", 'slug' => 'baseball', 'parent_slug' => 'sports'],
        ['name' => "Hockey", 'slug' => 'hockey', 'parent_slug' => 'sports'],
        ['name' => "Volleyball", 'slug' => 'volleyball', 'parent_slug' => 'sports'],
        ['name' => "Badminton", 'slug' => 'badminton', 'parent_slug' => 'sports'],
        ['name' => "Table Tennis", 'slug' => 'table-tennis', 'parent_slug' => 'sports'],
        ['name' => "Golf", 'slug' => 'golf', 'parent_slug' => 'sports'],
        ['name' => "Table and Board Games", 'slug' => 'table-board-games', 'parent_slug' => 'sports'],
        ['name' => "Shooting", 'slug' => 'shooting', 'parent_slug' => 'sports'],
        ['name' => "Inline & Roller Skates", 'slug' => 'inline-roller-skates', 'parent_slug' => 'sports'],
        ['name' => "Skateboards", 'slug' => 'skateboards', 'parent_slug' => 'sports'],
        ['name' => "Swimming Equipment", 'slug' => 'swimming-equipment', 'parent_slug' => 'sports'],
        ['name' => "Rugby", 'slug' => 'rugby', 'parent_slug' => 'sports'],
        ['name' => "Climbing", 'slug' => 'climbing', 'parent_slug' => 'sports'],
        ['name' => "Fishing", 'slug' => 'fishing', 'parent_slug' => 'sports'],
        ['name' => "Sports Shoes & Clothing", 'slug' => 'sports-shoes-clothing', 'parent_slug' => 'sports'],
        ['name' => "Self Defense Stick", 'slug' => 'self-defense-stick', 'parent_slug' => 'sports'],
        ['name' => "Exercise & Fitness", 'slug' => 'exercise-fitness', 'parent_slug' => 'sports'],
        ['name' => "Boxing, Martial Arts & MMA", 'slug' => 'boxing-mma', 'parent_slug' => 'sports'],
        ['name' => "Camping & Hiking", 'slug' => 'camping-hiking', 'parent_slug' => 'sports'],
        ['name' => "Cycling & Accessories", 'slug' => 'cycling-accessories', 'parent_slug' => 'sports'],
        ['name' => "Sports Accessories", 'slug' => 'sports-accessories', 'parent_slug' => 'sports'],
    ]);

    // Automotive & Motorbike
    $childCategories = array_merge($childCategories, [
        ['name' => "Motorcycle Riding Gear", 'slug' => 'motorcycle-riding-gear', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Lubricants & Solvents", 'slug' => 'lubricants-solvents', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Additives & Oils", 'slug' => 'additives-oils', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Moto Tires & Wheels", 'slug' => 'moto-tires-wheels', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Motorcycle Parts & Spares", 'slug' => 'motorcycle-parts', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Bike Accessories", 'slug' => 'bike-accessories', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "High-Pressure Water Gun", 'slug' => 'high-pressure-water', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Car Safety & Security", 'slug' => 'car-safety', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Car Mobile Charger", 'slug' => 'car-mobile-charger', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Car Exterior Accessories", 'slug' => 'car-exterior', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Car Interior Accessories", 'slug' => 'car-interior', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Batteries & Accessories", 'slug' => 'batteries-accessories', 'parent_slug' => 'automotive-motorbike'],
        ['name' => "Auto Tools & Equipment", 'slug' => 'auto-tools', 'parent_slug' => 'automotive-motorbike'],
    ]);


    foreach ($childCategories as $child) {
        DB::table('categories')->updateOrInsert(
            ['slug' => $child['slug']],
            [
                'name' => $child['name'],
                'parent_id' => $parentIds[$child['parent_slug']] ?? DB::table('categories')->where('slug', $child['parent_slug'])->value('id'),
                'is_active' => 1,
                'priority' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]
        );
    }

    return response()->json([
        'status' => true,
        'message' => 'All categories seeded successfully!',
        'parents_inserted' => count($parentCategories),
        'children_inserted' => count($childCategories)
    ]);
});
// Delete all categories - Admin only
Route::delete('/admin/categories/delete-all', function() {
    \DB::table('categories')->truncate();
    return response()->json(['status'=>true,'message'=>'Deleted']);
});




Route::get('/nai/kono/migrations', function () {

    return "Successfully done migraiton";
});

Route::get('/storage-link', function () {
    // Artisan::call('storage:link');
    Artisan::call('migrate');

    return response()->json([
        'status' => true,
        'message' => 'Successfully done storage link'
    ]);
});

// Mohasagor route
Route::get('/mohasagor/products', [MohasagorController::class, 'fetchData']);
// Route::get('/mohasagor/import', [MohasagorController1::class, 'fetchAndProcessProducts']);
Route::post('/admin/v1/mohasagor/import', [MohasagorController1::class, 'importProducts']);
// Public Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// --category seeder


// Public Store Routes
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/banners', [BannerController::class, 'index']);
Route::get('/settings', [SettingController::class, 'index']); // Public Settings

Route::fallback(function () {
    return response()->json([
        'status' => 'error',
        'message' => 'API route not found.'
    ], 404);
});

Route::group(['prefix' => 'v1'], function () {
    // Config - Public (optional authentication: validates token if present, allows access if not)
    Route::get('/config/app-config', [ConfigController::class, 'appConfig'])->middleware('optional.auth');


    // Authentication - Public
    Route::post('/auth/send-verification', [AuthController::class, 'sendVerification'])->middleware('optional.auth');
    Route::post('/auth/verify-otp', [AuthController::class, 'verification'])->middleware('optional.auth');
    Route::post('/auth/social-login', [AuthController::class, 'socialLogin']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/verify-reset-token', [AuthController::class, 'verifyResetToken']);
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword']);
    Route::post('/auth/reset-password/', [AuthController::class, 'resetPassword']); // With trailing slash
    Route::post('/auth/re-set-password', [AuthController::class, 'resetPassword']); // Alternative route format
    Route::post('/auth/re-set-password/', [AuthController::class, 'resetPassword']); // Alternative route format with trailing slash

    // Commerce - Public
    Route::get('/products', [CommerceController::class, 'index']);
    Route::get('/product-list', [CommerceController::class, 'productList']);
    Route::get('/product-detail/{id}', [CommerceController::class, 'productDetail']);
    Route::get('/brand-list', [CommerceController::class, 'brandList']);
    Route::get('/category-list', [CommerceController::class, 'categoryList']);
    Route::get('/category-list/{id}', [CommerceController::class, 'subcategoryList']);
    Route::get('/brand-with-products', [CommerceController::class, 'brandWithProducts']);
    Route::get('/brand-wise-products/{brand_id}', [CommerceController::class, 'brandWiseProducts']);
    Route::get('/categories-with-products', [CommerceController::class, 'categoriesWithProducts']);
    Route::get('/category-wise-products/{category_id}', [CommerceController::class, 'categoryWiseProducts']);
    Route::get('/items-sections', [CommerceController::class, 'productSections']);
    Route::get('/recommended-products', [CommerceController::class, 'recommendedProducts']);
    Route::get('/deal-of-the-day', [CommerceController::class, 'dealOfTheDay']);
    Route::get('/products/{product_id}/reviews', [OrderController::class, 'getReviews']);

    // Banner - Public
    Route::get('/flash-banners', [BannerController::class, 'flashBanners']);
    Route::get('/flash-banners/{id}', [BannerController::class, 'flashBannerDetail']);
    Route::get('/banners', [BannerController::class, 'banners']);
    Route::get('/banners/{id}', [BannerController::class, 'bannerDetail']);

    // Division, District, City - Public
    Route::get('/division/list', [ConfigController::class, 'divisionList'])->middleware('optional.auth');
    Route::get('/district/list', [ConfigController::class, 'districtList'])->middleware('optional.auth');
    Route::get('/city/list', [ConfigController::class, 'cityList'])->middleware('optional.auth');

    // Payment Callbacks - Public
    Route::get('/payment/success', [PaymentController::class, 'paymentSuccess']);
    Route::get('/payment/failed', [PaymentController::class, 'paymentFailed']);
    Route::get('/payment/cancel', [PaymentController::class, 'paymentCancel']);



    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/user', [AuthController::class, 'userInfo']);
        Route::match(['put', 'post'], '/auth/user', [AuthController::class, 'updateProfile'])
            ->middleware('parse.multipart.put');
        Route::put('/auth/update-fcm-token', [AuthController::class, 'updateFcmToken']);
        Route::delete('/auth/delete-account/{id}', [AuthController::class, 'deleteAccount']);


        // Shop routes
        Route::match(['put', 'post'], '/auth/update-shop-info', [AuthController::class, 'updateShopInfo'])
            ->middleware('parse.multipart.put');

        // Address routes
        Route::get('/auth/addresses', [AuthController::class, 'getAddresses']);
        Route::get('/auth/addresses/{id}', [AuthController::class, 'getAddress']);
        Route::post('/auth/addresses', [AuthController::class, 'createAddress']);
        Route::put('/auth/addresses/{id}', [AuthController::class, 'updateAddress']);
        Route::patch('/auth/addresses/{id}', [AuthController::class, 'updateAddress']);
        Route::delete('/auth/addresses/{id}', [AuthController::class, 'deleteAddress']);

        // Commerce protected routes
        Route::get('/favourites', [CommerceController::class, 'favouriteProducts']);
        Route::post('/favourites/add/{item_id}', [CommerceController::class, 'addFavourite']);
        Route::delete('/favourites/remove/{item_id}', [CommerceController::class, 'removeFavourite']);
        Route::get('/notifications', [CommerceController::class, 'notifications']);

        // Order routes
        Route::post('/order/cart', [OrderController::class, 'addToCart']);
        Route::put('/order/cart/{cart_id}', [OrderController::class, 'updateCart']);
        Route::get('/order/cart', [OrderController::class, 'cartList']);
        Route::delete('/order/cart/{item_id}', [OrderController::class, 'removeFromCart']);
        Route::post('/order/checkout', [OrderController::class, 'checkout']);
        Route::match(['post', 'delete'], '/order/cancel/{order_id}', [OrderController::class, 'cancelOrder']);
        Route::get('/order/info/{order_id}', [OrderController::class, 'orderInfoDetail']);
        Route::get('/order/info', [OrderController::class, 'ordersByStatus']);
        Route::post('/order/apply-coupon', [OrderController::class, 'applyCoupon']);
        Route::post('/order/reviews/post', [OrderController::class, 'postReview']);

        // Payment routes
        Route::post('/payment/init', [PaymentController::class, 'initPayment']);
        Route::post('/payment/complete', [PaymentController::class, 'completePayment']);
    });
});

//need admin api files call
// Include Admin Routes
Route::group(['prefix' => 'admin', 'middleware' => 'auth:sanctum'], function () {
    require base_path('routes/admin.php');
});
