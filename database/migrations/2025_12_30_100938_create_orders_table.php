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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('order_number')->unique();
            
            // Financials
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total_amount', 12, 2);
            $table->string('currency')->default('BDT');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            
            // Status & Flow
            $table->enum('status', [
                'pending', 'confirmed', 'purchased_by_admin', 
                'ready_to_ship_bd', 'shipping', 'delivered', 
                'cancelled', 'refunded'
            ])->default('pending');
            $table->enum('payment_status', ['unpaid', 'paid', 'partial'])->default('unpaid');
            $table->string('payment_method')->nullable();
            
            // Shipping Info
            $table->string('shipping_address');
            $table->string('contact_number');
            $table->text('notes')->nullable();
            $table->string('tracking_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
