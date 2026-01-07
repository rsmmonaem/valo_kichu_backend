<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Copy data from total_amount to total_price for existing records where total_price is missing
        // Verify total_amount exists first to avoid errors if it was already dropped (unlikely but safe)
        if (Schema::hasColumn('orders', 'total_amount') && Schema::hasColumn('orders', 'total_price')) {
             DB::statement('UPDATE orders SET total_price = total_amount WHERE total_price IS NULL OR total_price = 0');
        }

        // 2. Drop total_amount
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'total_amount')) {
                $table->dropColumn('total_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'total_amount')) {
                $table->decimal('total_amount', 12, 2)->default(0);
            }
        });
        
        // Restore data
        if (Schema::hasColumn('orders', 'total_amount')) {
            DB::statement('UPDATE orders SET total_amount = total_price');
        }
    }
};
