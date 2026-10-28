<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'price' => fake()->randomFloat(2, 0, 999999),
            'currency_id' => CurrencyFactory::new(),
            'tax_cost' => fake()->randomFloat(2, 0, 999999),
            'manufacturing_cost' => fake()->randomFloat(2, 0, 999999),
        ];
    }
}
