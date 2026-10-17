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
        if (!Schema::hasTable('search_synonyms')) {
            Schema::create('search_synonyms', function (Blueprint $table) {
                $table->id();
                $table->string('title')->comment('Human-friendly label e.g. Air Fryer');
                $table->json('terms')->comment('Array of words e.g. ["air fryer", "এয়ার ফ্রায়ার", "fryer"]');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_synonyms');
    }
};
