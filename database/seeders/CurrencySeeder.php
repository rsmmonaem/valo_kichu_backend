<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;
use App\Models\BusinessSetting;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $businessSettingId = BusinessSetting::getValue('business_setting_id', 1);
        
        if (!$businessSettingId) {
            $this->command->warn('BusinessSetting not found. Please run BusinessSettingSeeder first.');
            return;
        }

        $currencies = [
            [
                'business_setting_id' => $businessSettingId,
                'name' => 'US Dollar',
                'symbol' => '$',
                'currency_code' => 'USD',
                'exchange_rate' => 1.0000,
                'is_default' => true,
            ],
            [
                'business_setting_id' => $businessSettingId,
                'name' => 'Euro',
                'symbol' => '€',
                'currency_code' => 'EUR',
                'exchange_rate' => 0.9200,
                'is_default' => false,
            ],
            [
                'business_setting_id' => $businessSettingId,
                'name' => 'British Pound',
                'symbol' => '£',
                'currency_code' => 'GBP',
                'exchange_rate' => 0.7900,
                'is_default' => false,
            ],
            [
                'business_setting_id' => $businessSettingId,
                'name' => 'Japanese Yen',
                'symbol' => '¥',
                'currency_code' => 'JPY',
                'exchange_rate' => 150.0000,
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::firstOrCreate(
                [
                    'business_setting_id' => $currency['business_setting_id'],
                    'currency_code' => $currency['currency_code'],
                ],
                $currency
            );
        }
    }
}
