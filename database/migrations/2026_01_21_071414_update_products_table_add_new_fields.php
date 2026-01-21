<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // New fields based on your payload
            $table->string('product_sku')->nullable()->after('name');
            $table->string('product_type')->nullable()->after('product_sku');
            $table->decimal('unit_price', 10, 2)->default(0)->after('base_price');
            $table->decimal('purchase_price', 10, 2)->default(0)->after('unit_price');

            $table->integer('min_order_qty')->default(1)->after('stock_quantity');
            $table->integer('current_stock')->default(0)->after('min_order_qty');

            $table->enum('discount_type', ['None', 'Flat', 'Percentage'])->default('None')->after('sale_price');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('discount_type');

            $table->decimal('tax_amount', 10, 2)->default(0)->after('discount_amount');
            $table->string('tax_calculation')->nullable()->after('tax_amount');

            $table->decimal('shipping_cost', 10, 2)->default(0)->after('tax_calculation');
            $table->integer('shipping_multiply')->default(1)->after('shipping_cost');

            $table->decimal('loyalty_point', 10, 2)->default(0)->after('shipping_multiply');

            $table->json('variations')->nullable()->after('loyalty_point');
            $table->json('attributes')->nullable()->after('variations');
            $table->json('colors')->nullable()->after('attributes');
            $table->json('tags')->nullable()->after('colors');

            $table->string('status')->default('active')->after('tags');
            $table->boolean('is_trending')->default(false)->after('is_featured');
            $table->boolean('is_discounted')->default(false)->after('is_trending');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'product_sku', 'product_type', 'unit_price', 'purchase_price',
                'min_order_qty', 'current_stock', 'discount_type', 'discount_amount',
                'tax_amount', 'tax_calculation', 'shipping_cost', 'shipping_multiply',
                'loyalty_point', 'variations', 'attributes', 'colors', 'tags',
                'status', 'is_trending', 'is_discounted'
            ]);
        });
    }
};
