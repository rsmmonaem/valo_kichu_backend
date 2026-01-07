<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Language;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Language::updateOrCreate(
            ['code' => 'en'],
            [
                'name' => 'English',
                'language_code' => 'en',
                'app_lang_code' => 'en',
                'status' => true,
                'default' => true,
                'direction' => 'ltr',
                'business_setting_id' => 1
            ]
        );

        Language::updateOrCreate(
            ['code' => 'bd'],
            [
                'name' => 'Bangla',
                'language_code' => 'bd',
                'app_lang_code' => 'bd',
                'status' => true,
                'default' => false,
                'direction' => 'ltr',
                'business_setting_id' => 1
            ]
        );
    }
}
