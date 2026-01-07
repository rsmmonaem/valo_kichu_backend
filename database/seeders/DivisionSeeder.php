<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('divisions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('divisions')->insert([
            ['id' => 1, 'name' => 'Barishal'],
            ['id' => 2, 'name' => 'Chattogram'],
            ['id' => 3, 'name' => 'Dhaka'],
            ['id' => 4, 'name' => 'Khulna'],
            ['id' => 5, 'name' => 'Rajshahi'],
            ['id' => 6, 'name' => 'Rangpur'],
            ['id' => 7, 'name' => 'Mymensingh'],
            ['id' => 8, 'name' => 'Sylhet'],
        ]);
    }
}



// php artisan db:seed --class=DivisionSeeder
