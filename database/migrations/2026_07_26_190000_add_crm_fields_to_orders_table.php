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
            if (!Schema::hasColumn('orders', 'call_status')) {
                $table->string('call_status')->nullable()->default('pending');
            }
            if (!Schema::hasColumn('orders', 'last_called_at')) {
                $table->timestamp('last_called_at')->nullable();
            }
            if (!Schema::hasColumn('orders', 'crm_logs')) {
                $table->json('crm_logs')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'call_status')) {
                $table->dropColumn('call_status');
            }
            if (Schema::hasColumn('orders', 'last_called_at')) {
                $table->dropColumn('last_called_at');
            }
            if (Schema::hasColumn('orders', 'crm_logs')) {
                $table->dropColumn('crm_logs');
            }
        });
    }
};
