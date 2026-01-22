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
            $table->string('category')->nullable()->after('brand');
            $table->unsignedBigInteger('api_id')
                ->nullable()
                ->after('category');
            $table->unsignedBigInteger('product_code')
                ->nullable()
                ->after('api_id');
            $table->string('api_from')->nullable()->after('product_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'api_id',
                'product_code',
                'api_from'
            ]);
        });
    }
};
