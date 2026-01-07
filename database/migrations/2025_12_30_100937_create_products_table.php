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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            
            // Product Source Info
            $table->enum('source_type', ['api', 'admin'])->default('admin');
            $table->string('supplier_id')->nullable()->comment('For API products'); // 1688 Supplier/Product ID
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users');

            // Pricing & Stock (Base/Default)
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            
            // Meta
            $table->string('unit')->default('piece');
            $table->decimal('weight_kg', 8, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_deal_of_day')->default(false);
            $table->json('images')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
