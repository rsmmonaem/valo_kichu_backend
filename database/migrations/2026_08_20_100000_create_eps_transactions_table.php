<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the eps_transactions table for EPS Payment Gateway integration.
     * This table provides:
     * - Idempotency/deduplication via unique idempotency_key
     * - Internal transaction ID storage (merchant_transaction_id)
     * - Full audit trail of all EPS API interactions
     * - Server-side verification tracking
     */
    public function up(): void
    {
        Schema::create('eps_transactions', function (Blueprint $table) {
            $table->id();
            
            // Deduplication: Client-generated UUID, prevents duplicate payment initiations
            $table->uuid('idempotency_key')->unique();
            
            // Our invoice ID sent to EPS (merchantTransactionId)
            $table->string('merchant_transaction_id', 100)->unique();
            
            // EPS's own transaction ID (received from callback/verification)
            $table->string('eps_transaction_id', 100)->nullable()->index();
            
            // Relationships
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('payment_info_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            
            // Payment details
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('BDT');
            
            // Status tracking with finite states
            // initiated -> pending -> verified -> completed
            //                     -> failed
            //                     -> cancelled
            $table->string('status', 30)->default('initiated')->index();
            
            // Customer info snapshot (for audit, not dependent on users table)
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            
            // Full audit trail - store ALL API payloads
            $table->json('eps_request_payload')->nullable();      // What we sent to EPS Initialize
            $table->json('eps_response_payload')->nullable();     // What EPS returned from Initialize
            $table->json('eps_callback_payload')->nullable();     // GET params from success/fail/cancel redirect
            $table->json('eps_verification_payload')->nullable(); // CheckPaymentStatus response (server-side verify)
            
            // EPS redirect URL returned from initialization
            $table->text('redirect_url')->nullable();
            
            // Security & debugging
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            
            // Timestamps for each state transition
            $table->timestamp('initiated_at')->nullable();
            $table->timestamp('pending_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            // Verification tracking (how many times we called CheckPaymentStatus)
            $table->integer('verification_attempts')->default(0);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
            $table->foreign('payment_info_id')->references('id')->on('payment_info')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eps_transactions');
    }
};
