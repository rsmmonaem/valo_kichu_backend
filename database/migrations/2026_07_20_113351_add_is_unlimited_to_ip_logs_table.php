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
        Schema::table('ip_logs', function (Blueprint $table) {
            $table->boolean('is_unlimited')->default(false)->after('is_banned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ip_logs', function (Blueprint $table) {
            $table->dropColumn('is_unlimited');
        });
    }
};
