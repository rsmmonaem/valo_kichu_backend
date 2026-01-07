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
            if (Schema::hasColumn('products', 'images')) {
                $table->dropColumn('images');
            }
        });

        Schema::table('product_variations', function (Blueprint $table) {
            if (Schema::hasColumn('product_variations', 'images')) {
                $table->dropColumn('images');
            }
        });

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'image')) {
                $table->dropColumn('image');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('images')->nullable();
        });

        Schema::table('product_variations', function (Blueprint $table) {
            $table->json('images')->nullable();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('image')->nullable();
        });
    }
};
