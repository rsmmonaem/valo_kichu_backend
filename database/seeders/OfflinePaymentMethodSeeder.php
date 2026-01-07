<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\OfflinePaymentMethod;
use Illuminate\Support\Facades\DB;

class OfflinePaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'method_name' => 'Bkash',
                'method_fields' => [
                    [
                        'input_name' => 'personal',
                        'input_data' => '0140566585',
                        'input_type' => 'text',
                    ]
                ],
                'method_informations' => [
                    [
                        'customer_input' => 'name',
                        'customer_placeholder' => 'Enter your name',
                        'input_type' => 'text',
                        'is_required' => true,
                    ]
                ],
                'status' => 1,
            ],
            [
                'method_name' => 'Nagad',
                'method_fields' => [
                    [
                        'input_name' => '01405665854',
                        'input_data' => '01405665854',
                        'input_type' => 'text',
                    ]
                ],
                'method_informations' => [
                    [
                        'customer_input' => 'name',
                        'customer_placeholder' => 'Enter your name',
                        'input_type' => 'text',
                        'is_required' => true,
                    ]
                ],
                'status' => 1,
            ],
            [
                'method_name' => 'Rocket',
                'method_fields' => [
                    [
                        'input_name' => 'personal',
                        'input_data' => '0140566585',
                        'input_type' => 'text',
                    ]
                ],
                'method_informations' => [
                    [
                        'customer_input' => 'name',
                        'customer_placeholder' => 'Enter your name',
                        'input_type' => 'text',
                        'is_required' => true,
                    ]
                ],
                'status' => 1,
            ],
        ];

        foreach ($methods as $method) {
            OfflinePaymentMethod::updateOrCreate(
                ['method_name' => $method['method_name']],
                $method
            );
        }
    }
}
