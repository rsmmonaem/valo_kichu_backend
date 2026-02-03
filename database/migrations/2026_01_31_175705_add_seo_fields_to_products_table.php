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
            $table->string('meta_title')->nullable()->after('slug'); // SEO Title
            $table->text('meta_description')->nullable()->after('meta_title'); // SEO Description
            $table->string('meta_keywords')->nullable()->after('meta_description'); // SEO Keywords
            $table->string('meta_image')->nullable()->after('meta_keywords'); // SEO Image (OG Image)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_keywords', 'meta_image']);
        });
    }
};
