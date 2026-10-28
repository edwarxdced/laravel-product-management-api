<?php

namespace Database\Factories;

use App\Models\ProductPrice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductPrice>
 */
class ProductPriceFactory extends Factory
{
    protected $model = ProductPrice::class;

    public function definition(): array
    {
        return [
            'product_id' => ProductFactory::new(),
            'currency_id' => CurrencyFactory::new(),
            'price' => fake()->randomFloat(2, 0, 999999),
        ];
    }
}
