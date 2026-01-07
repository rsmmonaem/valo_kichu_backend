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
        if (!Schema::hasTable('brands')) {
            Schema::create('brands', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('image')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coupons')) {
            Schema::create('coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('type')->default('fixed'); // fixed, percentage
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('discount_rate', 5, 2)->default(0);
                $table->decimal('min_purchase_amount', 12, 2)->default(0);
                $table->decimal('max_discount_amount', 12, 2)->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->integer('rating');
                $table->text('comment')->nullable();
                $table->string('image')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('favourite_products')) {
            Schema::create('favourite_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('featured_products')) {
            Schema::create('featured_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payment_info')) {
            Schema::create('payment_info', function (Blueprint $table) {
                $table->id();
                $table->uuid('transaction_uuid')->unique();
                $table->string('user_full_name')->nullable();
                $table->string('user_phone')->nullable();
                $table->string('user_email')->nullable();
                $table->decimal('payment_amount', 12, 2);
                $table->string('currency')->default('BDT');
                $table->string('payment_gateway')->nullable();
                $table->string('transaction_id')->nullable();
                $table->string('bank_transaction_id')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->json('gateway_response')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('applied_coupons')) {
            Schema::create('applied_coupons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
                $table->boolean('is_used')->default(false);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('coupon_usages')) {
            Schema::create('coupon_usages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('coupon_id')->constrained()->onDelete('cascade');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('wallets')) {
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->decimal('balance', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cart_items')) {
            Schema::create('cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_id')->constrained()->onDelete('cascade');
                $table->foreignId('product_variation_id')->nullable()->constrained()->onDelete('cascade');
                $table->integer('quantity');
                $table->timestamp('added_at')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->timestamps();
            });
        }

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'address_id')) {
                $table->foreignId('address_id')->nullable()->constrained()->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->constrained('payment_info')->onDelete('set null');
            }
            if (!Schema::hasColumn('orders', 'transaction_id')) {
                $table->string('transaction_id')->nullable();
            }
            if (!Schema::hasColumn('orders', 'total_price')) {
                $table->decimal('total_price', 12, 2)->nullable();
            }
        });

        // Drop the legacy/unwanted table
        Schema::dropIfExists('recommended_products');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'address_id')) {
                $table->dropForeign(['address_id']);
                $table->dropColumn('address_id');
            }
            if (Schema::hasColumn('orders', 'payment_id')) {
                $table->dropForeign(['payment_id']);
                $table->dropColumn('payment_id');
            }
            if (Schema::hasColumn('orders', 'transaction_id')) {
                $table->dropColumn('transaction_id');
            }
            if (Schema::hasColumn('orders', 'total_price')) {
                $table->dropColumn('total_price');
            }
        });
        Schema::dropIfExists('cart_items');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('coupon_usages');
        Schema::dropIfExists('applied_coupons');
        Schema::dropIfExists('payment_info');
        Schema::dropIfExists('featured_products');
        Schema::dropIfExists('favourite_products');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('brands');
    }
};
