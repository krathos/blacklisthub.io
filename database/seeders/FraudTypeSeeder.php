<?php

namespace Database\Seeders;

use App\Models\FraudType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FraudTypeSeeder extends Seeder
{
    public function run(): void
    {
        $fraudTypes = [
            [
                'name' => 'Non Payment',
                'description' => 'Customer did not pay for products or services',
            ],
            [
                'name' => 'Chargeback Fraud',
                'description' => 'Customer initiated chargeback after receiving product/service',
            ],
            [
                'name' => 'Fake Information',
                'description' => 'Customer provided false personal or business information',
            ],
            [
                'name' => 'Stolen Credit Card',
                'description' => 'Customer used stolen or unauthorized credit card',
            ],
            [
                'name' => 'Package Theft',
                'description' => 'Customer claimed non-delivery or package theft fraudulently',
            ],
            [
                'name' => 'Identity Theft',
                'description' => 'Customer used stolen identity to make purchases',
            ],
            [
                'name' => 'Address Fraud',
                'description' => 'Customer provided false or misleading address information',
            ],
            [
                'name' => 'Return Fraud',
                'description' => 'Customer abused return policy or returned damaged/fake items',
            ],
            [
                'name' => 'Account Takeover',
                'description' => 'Customer account was compromised and used fraudulently',
            ],
            [
                'name' => 'Multiple Disputes',
                'description' => 'Customer has pattern of excessive disputes or complaints',
            ],
            [
                'name' => 'Other',
                'description' => 'Other type of fraud not listed above',
            ],
        ];

        foreach ($fraudTypes as $fraudType) {
            FraudType::create([
                'name' => $fraudType['name'],
                'slug' => Str::slug($fraudType['name']),
                'description' => $fraudType['description'],
                'is_active' => true,
            ]);
        }
    }
}