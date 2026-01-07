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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable(); // Matches language_code or code usage
            $table->string('language_code')->nullable(); // Explicitly requested in model
            $table->string('app_lang_code')->nullable();
            $table->string('image')->nullable();
            $table->boolean('status')->default(true);
            $table->boolean('default')->default(false);
            $table->string('direction')->default('ltr');
            $table->integer('business_setting_id')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
