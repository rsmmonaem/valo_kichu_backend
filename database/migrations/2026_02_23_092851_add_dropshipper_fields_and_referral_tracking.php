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
        // Update dropshippers table with personalization fields
        Schema::table('drop_shipers', function (Blueprint $table) {
            $table->string('store_logo')->nullable()->after('name');
            $table->string('store_banner')->nullable()->after('store_logo');
            $table->string('slogan')->nullable()->after('store_banner');
            $table->text('about_us')->nullable()->after('slogan');
            $table->json('social_links')->nullable()->after('about_us');
        });

        // Update orders table to track which dropshipper referred the order
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('referred_by_id')->nullable()->after('user_id')->constrained('users')->onDelete('set null');
            $table->string('referral_source')->nullable()->after('referred_by_id'); // e.g., 'store_link', 'api'
            $table->enum('order_type', ['direct', 'dropshipping', 'referral'])->default('direct')->after('referral_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drop_shipers', function (Blueprint $table) {
            $table->dropColumn(['store_logo', 'store_banner', 'slogan', 'about_us', 'social_links']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['referred_by_id']);
            $table->dropColumn(['referred_by_id', 'referral_source', 'order_type']);
        });
    }
};
