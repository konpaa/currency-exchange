<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $currencies = [
            ['code' => 'USD', 'name' => 'US Dollar'],
            ['code' => 'EUR', 'name' => 'Euro'],
            ['code' => 'RUB', 'name' => 'Russian Ruble'],
        ];

        foreach ($currencies as $item) {
            Currency::firstOrCreate(
                ['code' => $item['code']],
                ['name' => $item['name'], 'is_active' => true]
            );
        }
    }
}
