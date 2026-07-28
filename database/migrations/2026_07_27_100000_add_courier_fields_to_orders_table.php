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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'courier_name')) {
                $table->string('courier_name')->nullable()->after('status');
            }
            if (!Schema::hasColumn('orders', 'courier_consignment_id')) {
                $table->string('courier_consignment_id')->nullable()->after('courier_name');
            }
            if (!Schema::hasColumn('orders', 'courier_status')) {
                $table->string('courier_status')->nullable()->after('courier_consignment_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'courier_name')) {
                $table->dropColumn('courier_name');
            }
            if (Schema::hasColumn('orders', 'courier_consignment_id')) {
                $table->dropColumn('courier_consignment_id');
            }
            if (Schema::hasColumn('orders', 'courier_status')) {
                $table->dropColumn('courier_status');
            }
        });
    }
};
