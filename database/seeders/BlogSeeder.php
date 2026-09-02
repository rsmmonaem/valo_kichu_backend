<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // Clear existing blogs first to avoid duplicate errors
        Blog::truncate();

        $blogs = [
            [
                'title' => 'Top 10 Fashion Trends You Need to Know This Season',
                'description' => '<h2>Stay Ahead of the Curve</h2><p>From bold color palettes to oversized silhouettes, this season brings exciting changes to the fashion landscape. Discover the must-have pieces that will keep your wardrobe fresh and stylish all year round.</p><h3>1. Earthy Tones</h3><p>Natural, earthy colors like terracotta, sage green, and warm beige are dominating the fashion scene. These versatile shades pair beautifully with both casual and formal outfits.</p><h3>2. Oversized Everything</h3><p>From blazers to knitwear, the oversized trend continues to reign supreme. Embrace comfort without sacrificing style with relaxed-fit pieces.</p><h3>3. Statement Accessories</h3><p>Chunky chains, bold earrings, and eye-catching bags are the finishing touches that elevate any look from ordinary to extraordinary.</p><p>Whether you prefer minimalist elegance or statement-making outfits, there is something for everyone this season!</p>',
                'views' => 1250,
                'is_featured' => true,
                'meta_title' => 'Top Fashion Trends 2026 - Valo Kichu Blog',
                'meta_description' => 'Discover the hottest fashion trends for this season. From earthy tones to oversized silhouettes, stay stylish with our expert guide.',
                'meta_keywords' => 'fashion, trends, style, clothing, 2026 fashion',
            ],
            [
                'title' => 'Best Tech Gadgets for Your Home Office in 2026',
                'description' => '<h2>Upgrade Your Workspace</h2><p>Working from home has become the new normal. Upgrade your workspace with these cutting-edge gadgets that boost productivity and comfort.</p><h3>Ultra-Wide Monitors</h3><p>A spacious display is essential for multitasking. The latest ultra-wide monitors offer stunning visuals and seamless split-screen capabilities.</p><h3>Ergonomic Keyboards</h3><p>Invest in a mechanical keyboard with ergonomic design to reduce strain during long work sessions. Your wrists will thank you.</p><h3>Noise-Cancelling Headphones</h3><p>Block out distractions with premium noise-cancelling headphones. Perfect for focus time and virtual meetings alike.</p>',
                'views' => 980,
                'is_featured' => true,
                'meta_title' => 'Best Home Office Gadgets 2026 - Tech Guide',
                'meta_description' => 'Discover the best tech gadgets for your home office setup. Boost productivity with our expert picks for 2026.',
                'meta_keywords' => 'tech, gadgets, home office, productivity, workspace',
            ],
            [
                'title' => 'Transform Your Living Space: Modern Home Decor Ideas',
                'description' => '<h2>Create Your Dream Home</h2><p>Your home should reflect your personality. Explore creative decor ideas that blend comfort with aesthetics.</p><h3>Scandinavian Minimalism</h3><p>Clean lines, natural materials, and a neutral color palette create a serene atmosphere. Less truly is more when it comes to Scandinavian design.</p><h3>Biophilic Design</h3><p>Bringing nature indoors with plants, natural light, and organic textures creates a calming environment that promotes well-being.</p><h3>Statement Lighting</h3><p>A single striking light fixture can transform an entire room. From pendant lights to sculptural floor lamps, lighting is the new art.</p>',
                'views' => 760,
                'is_featured' => true,
                'meta_title' => 'Modern Home Decor Ideas - Interior Design Tips',
                'meta_description' => 'Transform your living space with modern home decor ideas. From Scandinavian minimalism to biophilic design.',
                'meta_keywords' => 'home decor, interior design, living room, decoration',
            ],
            [
                'title' => 'The Ultimate Skincare Routine for Glowing Skin',
                'description' => '<h2>Your Path to Radiant Skin</h2><p>Achieving radiant, healthy skin does not require expensive treatments. Discover the step-by-step skincare routine recommended by dermatologists.</p><h3>Step 1: Double Cleanse</h3><p>Start with an oil-based cleanser to remove makeup and sunscreen, followed by a water-based cleanser for a deep clean.</p><h3>Step 2: Tone</h3><p>A hydrating toner restores your skin pH balance and prepares it for the next steps.</p><h3>Step 3: Serum</h3><p>Target specific concerns with a concentrated serum. Vitamin C for brightness, hyaluronic acid for hydration, or niacinamide for pore refinement.</p><h3>Step 4: Moisturize & Protect</h3><p>Lock in moisture with a lightweight moisturizer and always finish with SPF during the day.</p>',
                'views' => 2100,
                'is_featured' => true,
                'meta_title' => 'Ultimate Skincare Routine - Beauty Guide',
                'meta_description' => 'Learn the perfect skincare routine for glowing skin. Expert dermatologist-recommended steps and products.',
                'meta_keywords' => 'skincare, beauty, routine, glowing skin, moisturizer',
            ],
            [
                'title' => 'Smart Shopping Tips: How to Get the Best Deals Online',
                'description' => '<h2>Shop Smarter, Not Harder</h2><p>Save money without sacrificing quality! Learn the insider tricks to finding the best deals online.</p><h3>Timing is Everything</h3><p>Major sales events like Black Friday, Cyber Monday, and seasonal clearances offer the deepest discounts. Plan your purchases around these dates.</p><h3>Use Price Comparison Tools</h3><p>Browser extensions and apps can automatically compare prices across multiple retailers, ensuring you never overpay.</p><h3>Stack Your Savings</h3><p>Combine coupon codes, cashback apps, and loyalty rewards for maximum savings on every purchase.</p>',
                'views' => 540,
                'is_featured' => false,
                'meta_title' => 'Smart Online Shopping Tips - Save Money Guide',
                'meta_description' => 'Learn how to get the best deals online with our smart shopping tips. Save money with expert strategies.',
                'meta_keywords' => 'shopping, deals, online shopping, tips, savings',
            ],
            [
                'title' => 'Stay Active: Top Fitness Products for Your Daily Workout',
                'description' => '<h2>Gear Up for Success</h2><p>Whether you are a beginner or a seasoned athlete, having the right fitness gear makes all the difference.</p><h3>Resistance Bands</h3><p>Versatile and portable, resistance bands provide a full-body workout anywhere. Perfect for strength training and rehabilitation.</p><h3>Smart Water Bottles</h3><p>Stay hydrated with bottles that track your water intake and remind you to drink throughout the day.</p><h3>Fitness Trackers</h3><p>Monitor your heart rate, steps, sleep quality, and workout intensity with the latest wearable technology.</p>',
                'views' => 890,
                'is_featured' => false,
                'meta_title' => 'Top Fitness Products 2026 - Workout Essentials',
                'meta_description' => 'Discover the best fitness products for your daily workout. From resistance bands to smart trackers.',
                'meta_keywords' => 'fitness, workout, exercise, health, gym equipment',
            ],
        ];

        foreach ($blogs as $blogData) {
            Blog::create(array_merge($blogData, [
                'slug' => Str::slug($blogData['title']),
                'status' => true,
            ]));
        }

        $this->command->info('✅ ' . count($blogs) . ' demo blog posts seeded successfully!');
    }
}
