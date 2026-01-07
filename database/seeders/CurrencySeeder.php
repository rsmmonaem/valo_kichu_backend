<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            [
                'currency_code' => 'BDT',
                'symbol' => '৳',
                'exchange_rate' => 1.00,
                'is_default' => true,
            ],
            [
                'currency_code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 0.0083,
                'is_default' => false,
            ],
            [
                'currency_code' => 'AED',
                'symbol' => 'د.إ',
                'exchange_rate' => 0.031,
                'is_default' => false,
            ],
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['currency_code' => $currency['currency_code']],
                $currency
            );
        }
    }
}
