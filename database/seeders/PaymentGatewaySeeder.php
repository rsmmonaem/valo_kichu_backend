<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentGateway;
use Illuminate\Support\Str;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'id' => 'ea346efe-cdda-11ed-affe-0c7a158e4469',
                'key' => 'ssl_commerz',
                'mode' => 'live',
                'is_active' => true,
                'live_values' => [
                    'gateway' => 'ssl_commerz',
                    'mode' => 'live',
                    'status' => 1,
                    'gateway_title' => 'Ssl commerz',
                    'gateway_image' => '2023-08-30-03-43-49-64ef0a85d5e5f.png',
                ],
                'test_values' => [
                    'gateway' => 'ssl_commerz',
                    'mode' => 'test',
                    'status' => 1,
                    'gateway_title' => 'Ssl commerz',
                    'gateway_image' => '2023-08-30-03-43-49-64ef0a85d5e5f.png',
                ],
            ],
            [
                'id' => Str::uuid()->toString(),
                'key' => 'bkash',
                'mode' => 'live',
                'is_active' => true,
                'live_values' => [
                    'gateway' => 'bkash',
                    'mode' => 'live',
                    'status' => 1,
                    'gateway_title' => 'Bkash',
                    'gateway_image' => null,
                ],
                'test_values' => [
                    'gateway' => 'bkash',
                    'mode' => 'test',
                    'status' => 1,
                    'gateway_title' => 'Bkash',
                    'gateway_image' => null,
                ],
            ],
            [
                'id' => Str::uuid()->toString(),
                'key' => 'nagad',
                'mode' => 'live',
                'is_active' => true,
                'live_values' => [
                    'gateway' => 'nagad',
                    'mode' => 'live',
                    'status' => 1,
                    'gateway_title' => 'Nagad',
                    'gateway_image' => null,
                ],
                'test_values' => [
                    'gateway' => 'nagad',
                    'mode' => 'test',
                    'status' => 1,
                    'gateway_title' => 'Nagad',
                    'gateway_image' => null,
                ],
            ],
            [
                'id' => Str::uuid()->toString(),
                'key' => 'stripe',
                'mode' => 'test',
                'is_active' => false,
                'live_values' => [
                    'gateway' => 'stripe',
                    'mode' => 'live',
                    'status' => 0,
                    'gateway_title' => 'Stripe',
                    'gateway_image' => null,
                ],
                'test_values' => [
                    'gateway' => 'stripe',
                    'mode' => 'test',
                    'status' => 0,
                    'gateway_title' => 'Stripe',
                    'gateway_image' => null,
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['key' => $gateway['key']],
                $gateway
            );
        }
    }
}
