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
            $table->decimal('dropshipper_margin', 5, 2)->default(0)->after('refer_by');
            // Adding a comment to the role column to document the new roles
            // (Note: Not changing the enum type to avoid DB specific issues if it's already defined as string)
            $table->string('role')->change()->comment('admin, sub_admin, user, dropshipper, sub_dropshipper, sub_sub_dropshipper');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('dropshipper_margin');
        });
    }
};
