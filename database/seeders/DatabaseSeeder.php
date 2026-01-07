<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        $this->command->info('Seeding Users...');
        
        // Super Admin
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'phone_number' => '01700000000',
                'password' => Hash::make('12345678'),
                'role' => 'super_admin',
                'status' => true,
            ]
        );

        // Customer
        User::firstOrCreate(
            ['email' => 'customer@test.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'Customer',
                'phone_number' => '01800000000',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'status' => true,
            ]
        );

        // 2. Seed Categories
        $this->command->info('Seeding Categories...');
        // Categories with Real Images
        $categories = [
            ['name' => 'Bags', 'slug' => 'bags', 'image' => 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=500&q=80'],
            ['name' => 'Shoes', 'slug' => 'shoes', 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&q=80'],
            ['name' => 'Jewelry', 'slug' => 'jewelry', 'image' => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500&q=80'],
            ['name' => 'Beauty', 'slug' => 'beauty', 'image' => 'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=500&q=80'],
            ['name' => 'Men\'s Clothing', 'slug' => 'mens-clothing', 'image' => 'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=500&q=80'],
            ['name' => 'Women\'s Clothing', 'slug' => 'womens-clothing', 'image' => 'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&q=80'],
            ['name' => 'Baby Items', 'slug' => 'baby-items', 'image' => 'https://images.unsplash.com/photo-1515488042361-ee0065af498c?w=500&q=80'],
            ['name' => 'Sunglasses', 'slug' => 'sunglasses', 'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=500&q=80'],
            ['name' => 'Gadgets', 'slug' => 'gadgets', 'image' => 'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=500&q=80'],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'image' => $cat['image'],
                    'is_active' => true,
                ]
            );
        }

        // 3. Seed Products with Real Images per Category
        $this->command->info('Seeding Products...');
        $categoryModels = Category::all();

        $categoryImages = [
            'bags' => [
                'https://images.unsplash.com/photo-1590874103328-3607fa60081d?w=500&q=80',
                'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=500&q=80',
                'https://images.unsplash.com/photo-1591561954557-26941169b49e?w=500&q=80',
                'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=500&q=80',
            ],
            'shoes' => [
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&q=80',
                'https://images.unsplash.com/photo-1549298916-b41d501d3772?w=500&q=80',
                'https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?w=500&q=80',
                'https://images.unsplash.com/photo-1560769629-975ec94e6a86?w=500&q=80',
            ],
            'jewelry' => [
                'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=500&q=80',
                'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=500&q=80',
                'https://images.unsplash.com/photo-1599643478518-17488fbbcd75?w=500&q=80',
                'https://images.unsplash.com/photo-1611085583191-a3b181a88401?w=500&q=80',
            ],
            'beauty' => [
                'https://images.unsplash.com/photo-1596462502278-27bfdd403348?w=500&q=80',
                'https://images.unsplash.com/photo-1522335789203-abd65232231d?w=500&q=80',
                'https://images.unsplash.com/photo-1512496015851-a90fb38ba796?w=500&q=80',
                'https://images.unsplash.com/photo-1616683693504-3ea7e9ad6fec?w=500&q=80',
            ],
             'mens-clothing' => [
                'https://images.unsplash.com/photo-1490114538077-0a7f8cb49891?w=500&q=80',
                'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=500&q=80',
                'https://images.unsplash.com/photo-1617137968427-85924c809a10?w=500&q=80',
                'https://images.unsplash.com/photo-1516257984-b1b4d8c9230c?w=500&q=80',
            ],
             'womens-clothing' => [
                'https://images.unsplash.com/photo-1517841905240-472988babdf9?w=500&q=80',
                'https://images.unsplash.com/photo-1483985988355-763728e1935b?w=500&q=80',
                'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=500&q=80',
                'https://images.unsplash.com/photo-1529139574466-a302d2d3f52c?w=500&q=80',
            ],
            // Default fallback
            'default' => [
                'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500&q=80',
                'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500&q=80',
                'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=500&q=80',
                'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=500&q=80',
            ]
        ];

        foreach ($categoryModels as $category) {
            $images = $categoryImages[$category->slug] ?? $categoryImages['default'];
            
            for ($i = 1; $i <= 10; $i++) {
                // Pick random image from category set
                $mainImage = $images[array_rand($images)];
                $detailImage1 = $images[array_rand($images)];
                $detailImage2 = $images[array_rand($images)];

                $product = Product::create([
                    'name' => "{$category->name} Item {$i} - Premium Quality",
                    'slug' => Str::slug("{$category->name} {$i} " . Str::random(5)),
                    'description' => "This is a premium high-quality {$category->name} item sourced directly from top manufacturers. It features durable materials, modern design, and exceptional craftsmanship tailored for the international market.",
                    'category_id' => $category->id,
                    'source_type' => 'api',
                    'supplier_id' => '1688-DEMO-' . $i,
                    'base_price' => rand(500, 5000),
                    'sale_price' => rand(0, 1) ? rand(300, 4000) : null,
                    'stock_quantity' => rand(10, 100),
                    'unit' => 'piece',
                    'weight_kg' => rand(1, 50) / 10,
                    'is_active' => true,
                    'is_featured' => rand(0, 1),
                    'images' => [$mainImage, $detailImage1, $detailImage2],
                ]);

                // Create variations
                $colors = ['Red', 'Blue', 'Black', 'White', 'Silver'];
                $sizes = ['S', 'M', 'L', 'XL', 'Free Size'];
                
                foreach (array_rand(array_flip($colors), rand(2, 4)) as $color) {
                    foreach (array_rand(array_flip($sizes), rand(2, 3)) as $size) {
                        $product->variations()->create([
                            'color' => $color,
                            'size' => $size,
                            'price_modifier' => rand(0, 200),
                            'stock_quantity' => rand(5, 20),
                            'sku' => strtoupper(Str::random(8)),
                        ]);
                    }
                }
            }
        }

        // 4. Seed Banners
        $this->command->info('Seeding Banners...');
        $banners = [
            [
                'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1200&q=80',
                'title' => 'Direct from 1688 Manufacturers',
                'subtitle' => 'Premium Wholesale',
                'link' => '/products',
                'order_index' => 0
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1472851294608-062f824d29cc?w=1200&q=80',
                'title' => 'New Season Collection',
                'subtitle' => 'Latest Trends',
                'link' => '/products?category=shoes',
                'order_index' => 1
            ],
            [
                'image_url' => 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=1200&q=80',
                'title' => 'Big Sale Event',
                'subtitle' => 'Up to 50% Off',
                'link' => '/products',
                'order_index' => 2
            ]
        ];

        foreach ($banners as $banner) {
            \App\Models\Banner::updateOrCreate(
                ['image_url' => $banner['image_url']],
                $banner
            );
        }

        // 5. Seed Settings
        $this->command->info('Seeding Settings...');
        \App\Models\BusinessSetting::updateOrCreate(
            ['key' => 'site_logo'],
            ['value' => 'https://raw.githubusercontent.com/rsmmonaem/rsmmonaem/main/logos/safayat_logo.png', 'type' => 'string']
        );
        
        $this->command->info('Database Seeded Successfully!');
    }
}
