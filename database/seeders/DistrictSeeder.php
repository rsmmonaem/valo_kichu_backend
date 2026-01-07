<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DistrictSeeder extends Seeder
{
    public function run(): void
    {
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('districts')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::table('districts')->insert([
            ['id' => 1, 'name' => 'Dhaka', 'division_id' => 3],
            ['id' => 2, 'name' => 'Faridpur', 'division_id' => 3],
            ['id' => 3, 'name' => 'Gazipur', 'division_id' => 3],
            ['id' => 4, 'name' => 'Gopalganj', 'division_id' => 3],
            ['id' => 5, 'name' => 'Kishoreganj', 'division_id' => 3],
            ['id' => 6, 'name' => 'Madaripur', 'division_id' => 3],
            ['id' => 7, 'name' => 'Manikganj', 'division_id' => 3],
            ['id' => 8, 'name' => 'Munshiganj', 'division_id' => 3],
            ['id' => 9, 'name' => 'Narayanganj', 'division_id' => 3],
            ['id' => 10, 'name' => 'Narsingdi', 'division_id' => 3],
            ['id' => 11, 'name' => 'Rajbari', 'division_id' => 3],
            ['id' => 12, 'name' => 'Shariatpur', 'division_id' => 3],
            ['id' => 13, 'name' => 'Tangail', 'division_id' => 3],
            ['id' => 14, 'name' => 'Jamalpur', 'division_id' => 7],
            ['id' => 15, 'name' => 'Sherpur', 'division_id' => 7],
            ['id' => 16, 'name' => 'Netrokona', 'division_id' => 7],
            ['id' => 17, 'name' => 'Mymensingh', 'division_id' => 7],
            ['id' => 18, 'name' => 'Bogra', 'division_id' => 5],
            ['id' => 19, 'name' => 'Joypurhat', 'division_id' => 5],
            ['id' => 20, 'name' => 'Naogaon', 'division_id' => 5],
            ['id' => 21, 'name' => 'Natore', 'division_id' => 5],
            ['id' => 22, 'name' => 'Chapainawabganj', 'division_id' => 5],
            ['id' => 23, 'name' => 'Pabna', 'division_id' => 5],
            ['id' => 24, 'name' => 'Rajshahi', 'division_id' => 5],
            ['id' => 25, 'name' => 'Sirajgonj', 'division_id' => 5],
            ['id' => 26, 'name' => 'Dinajpur', 'division_id' => 6],
            ['id' => 27, 'name' => 'Gaibandha', 'division_id' => 6],
            ['id' => 28, 'name' => 'Kurigram', 'division_id' => 6],
            ['id' => 29, 'name' => 'Lalmonirhat', 'division_id' => 6],
            ['id' => 30, 'name' => 'Nilphamari', 'division_id' => 6],
            ['id' => 31, 'name' => 'Panchagarh', 'division_id' => 6],
            ['id' => 32, 'name' => 'Rangpur', 'division_id' => 6],
            ['id' => 33, 'name' => 'Thakurgaon', 'division_id' => 6],
            ['id' => 34, 'name' => 'Barguna', 'division_id' => 1],
            ['id' => 35, 'name' => 'Barisal', 'division_id' => 1],
            ['id' => 36, 'name' => 'Bhola', 'division_id' => 1],
            ['id' => 37, 'name' => 'Jhalokati', 'division_id' => 1],
            ['id' => 38, 'name' => 'Patuakhali', 'division_id' => 1],
            ['id' => 39, 'name' => 'Pirojpur', 'division_id' => 1],
            ['id' => 40, 'name' => 'Bandarban', 'division_id' => 2],
            ['id' => 41, 'name' => 'Brahmanbaria', 'division_id' => 2],
            ['id' => 42, 'name' => 'Chandpur', 'division_id' => 2],
            ['id' => 43, 'name' => 'Chittagong', 'division_id' => 2],
            ['id' => 44, 'name' => 'Comilla', 'division_id' => 2],
            ['id' => 45, 'name' => 'Cox s Bazar', 'division_id' => 2],
            ['id' => 46, 'name' => 'Feni', 'division_id' => 2],
            ['id' => 47, 'name' => 'Khagrachari', 'division_id' => 2],
            ['id' => 48, 'name' => 'Lakshmipur', 'division_id' => 2],
            ['id' => 49, 'name' => 'Noakhali', 'division_id' => 2],
            ['id' => 50, 'name' => 'Rangamati', 'division_id' => 2],
            ['id' => 51, 'name' => 'Habiganj', 'division_id' => 8],
            ['id' => 52, 'name' => 'Maulvibazar', 'division_id' => 8],
            ['id' => 53, 'name' => 'Sunamganj', 'division_id' => 8],
            ['id' => 54, 'name' => 'Sylhet', 'division_id' => 8],
            ['id' => 55, 'name' => 'Bagerhat', 'division_id' => 4],
            ['id' => 56, 'name' => 'Chuadanga', 'division_id' => 4],
            ['id' => 57, 'name' => 'Jessore', 'division_id' => 4],
            ['id' => 58, 'name' => 'Jhenaidah', 'division_id' => 4],
            ['id' => 59, 'name' => 'Khulna', 'division_id' => 4],
            ['id' => 60, 'name' => 'Kushtia', 'division_id' => 4],
            ['id' => 61, 'name' => 'Magura', 'division_id' => 4],
            ['id' => 62, 'name' => 'Meherpur', 'division_id' => 4],
            ['id' => 63, 'name' => 'Narail', 'division_id' => 4],
            ['id' => 64, 'name' => 'Satkhira', 'division_id' => 4],
        ]);
    }
}


//php artisan db:seed --class=DistrictSeeder
