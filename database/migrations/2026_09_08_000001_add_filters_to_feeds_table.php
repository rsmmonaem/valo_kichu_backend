<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('feeds') && !Schema::hasColumn('feeds', 'filters')) {
            Schema::table('feeds', function (Blueprint $table) {
                $table->json('filters')->nullable()->after('format');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('feeds') && Schema::hasColumn('feeds', 'filters')) {
            Schema::table('feeds', function (Blueprint $table) {
                $table->dropColumn('filters');
            });
        }
    }
};
