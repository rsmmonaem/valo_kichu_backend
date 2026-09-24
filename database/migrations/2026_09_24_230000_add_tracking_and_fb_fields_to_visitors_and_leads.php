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
        // Add tracking and FB attribution fields to visitors table
        Schema::table('visitors', function (Blueprint $table) {
            if (!Schema::hasColumn('visitors', 'last_visited_at')) {
                $table->timestamp('last_visited_at')->nullable()->after('location')->index();
            }
            if (!Schema::hasColumn('visitors', 'fb_event_id')) {
                $table->string('fb_event_id')->nullable()->after('last_visited_at');
            }
            if (!Schema::hasColumn('visitors', 'fbp')) {
                $table->string('fbp')->nullable()->after('fb_event_id');
            }
            if (!Schema::hasColumn('visitors', 'fbc')) {
                $table->string('fbc')->nullable()->after('fbp');
            }
            if (!Schema::hasColumn('visitors', 'device_type')) {
                $table->string('device_type', 50)->nullable()->after('fbc');
            }
            if (!Schema::hasColumn('visitors', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('visitors', 'referrer')) {
                $table->text('referrer')->nullable()->after('user_agent');
            }
        });

        // Add fb_event_id to visitor_page_views table
        Schema::table('visitor_page_views', function (Blueprint $table) {
            if (!Schema::hasColumn('visitor_page_views', 'fb_event_id')) {
                $table->string('fb_event_id')->nullable()->after('url');
            }
        });

        // Add tracking & attribution fields to checkout_leads table
        Schema::table('checkout_leads', function (Blueprint $table) {
            if (!Schema::hasColumn('checkout_leads', 'ip_address')) {
                $table->string('ip_address', 45)->nullable()->after('cart_data');
            }
            if (!Schema::hasColumn('checkout_leads', 'fb_event_id')) {
                $table->string('fb_event_id')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('checkout_leads', 'fbp')) {
                $table->string('fbp')->nullable()->after('fb_event_id');
            }
            if (!Schema::hasColumn('checkout_leads', 'fbc')) {
                $table->string('fbc')->nullable()->after('fbp');
            }
            if (!Schema::hasColumn('checkout_leads', 'user_agent')) {
                $table->text('user_agent')->nullable()->after('fbc');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn([
                'last_visited_at',
                'fb_event_id',
                'fbp',
                'fbc',
                'device_type',
                'user_agent',
                'referrer',
            ]);
        });

        Schema::table('visitor_page_views', function (Blueprint $table) {
            $table->dropColumn(['fb_event_id']);
        });

        Schema::table('checkout_leads', function (Blueprint $table) {
            $table->dropColumn([
                'ip_address',
                'fb_event_id',
                'fbp',
                'fbc',
                'user_agent',
            ]);
        });
    }
};
