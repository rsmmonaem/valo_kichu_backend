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
        Schema::table('users', function (Blueprint $table) {
            // Add missing columns if they don't exist
            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->after('email');
            }
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable()->after('password');
            }
            if (!Schema::hasColumn('users', 'last_name')) {
                $table->string('last_name')->nullable()->after('first_name');
            }
            if (!Schema::hasColumn('users', 'gender')) {
                $table->enum('gender', ['Male', 'Female', 'Other'])->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }

            // Ensure constraints
            $table->string('email')->nullable(false)->change();
            $table->string('phone_number')->nullable(false)->change();
        });

        // Add unique constraint separately to avoid issues if phone_number already has data (though unlikely here)
        Schema::table('users', function (Blueprint $table) {
            try {
                $table->unique('phone_number');
            } catch (\Exception $e) {
                // Ignore if already unique
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
            $table->dropColumn(['phone_number', 'first_name', 'last_name', 'gender', 'date_of_birth']);
            $table->string('email')->nullable()->change();
        });
    }
};
