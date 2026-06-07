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
        Schema::create('ip_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->unique();
            $table->integer('request_count')->default(0);
            $table->timestamp('last_request_at')->nullable();
            $table->boolean('is_banned')->default(false);
            $table->text('ban_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ip_logs');
    }
};
