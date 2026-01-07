<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppVersionControl;

class AppVersionControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $appVersions = [
            // User App - Android
            [
                'device' => 'android',
                'app' => 'user_app',
                'status' => true,
                'version' => 1.0,
                'link' => null,
            ],
            // User App - iOS
            [
                'device' => 'ios',
                'app' => 'user_app',
                'status' => true,
                'version' => 1.0,
                'link' => null,
            ],
            // Seller App - Android
            [
                'device' => 'android',
                'app' => 'seller_app',
                'status' => true,
                'version' => 1.0,
                'link' => null,
            ],
            // Seller App - iOS
            [
                'device' => 'ios',
                'app' => 'seller_app',
                'status' => true,
                'version' => 1.0,
                'link' => null,
            ],
            // Delivery App - Android
            [
                'device' => 'android',
                'app' => 'delivery_app',
                'status' => true,
                'version' => 1.0,
                'link' => null,
            ],
            // Delivery App - iOS
            [
                'device' => 'ios',
                'app' => 'delivery_app',
                'status' => true,
                'version' => 1.0,
                'link' => null,
            ],
        ];

        foreach ($appVersions as $version) {
            AppVersionControl::updateOrCreate(
                [
                    'device' => $version['device'],
                    'app' => $version['app'],
                ],
                $version
            );
        }
    }
}
