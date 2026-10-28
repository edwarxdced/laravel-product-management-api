<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            ['name' => 'US Dollar', 'symbol' => '$', 'exchange_rate' => 1.0],
            ['name' => 'Euro', 'symbol' => '€', 'exchange_rate' => 0.92],
        ];

        foreach ($currencies as $currency) {
            Currency::create($currency);
        }

    }
}
