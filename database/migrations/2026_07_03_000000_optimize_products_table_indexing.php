<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add brand_id column to products table if it doesn't exist
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'brand_id')) {
                $table->foreignId('brand_id')
                    ->nullable()
                    ->after('category_id')
                    ->constrained('brands')
                    ->onDelete('set null');
            }

            // Index status and created_at if they don't already have indexes
            // Note: category_id already has a foreign key constraint, which automatically indexes it in MySQL.
            $indexes = Schema::getIndexes('products');
            $indexNames = array_column($indexes, 'name');

            if (!in_array('products_status_index', $indexNames)) {
                $table->index('status', 'products_status_index');
            }
            if (!in_array('products_created_at_index', $indexNames)) {
                $table->index('created_at', 'products_created_at_index');
            }
        });

        // 2. Migrate existing string 'brand' values to 'brand_id'
        $products = DB::table('products')->whereNotNull('brand')->get();
        foreach ($products as $product) {
            $brandName = trim($product->brand);
            if (empty($brandName) || strtolower($brandName) === 'unknown') {
                continue;
            }

            // Find or create the brand in the brands table
            $brandSlug = Str::slug($brandName);
            $brand = DB::table('brands')->where('slug', $brandSlug)->first();

            if (!$brand) {
                $brandId = DB::table('brands')->insertGetId([
                    'name' => $brandName,
                    'slug' => $brandSlug,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $brandId = $brand->id;
            }

            DB::table('products')->where('id', $product->id)->update([
                'brand_id' => $brandId
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'brand_id')) {
                $table->dropForeign(['brand_id']);
                $table->dropColumn('brand_id');
            }

            $indexes = Schema::getIndexes('products');
            $indexNames = array_column($indexes, 'name');

            if (in_array('products_status_index', $indexNames)) {
                $table->dropIndex('products_status_index');
            }
            if (in_array('products_created_at_index', $indexNames)) {
                $table->dropIndex('products_created_at_index');
            }
        });
    }
};
