<?php

namespace Tests\Feature;

use App\Models\ProductPrice;
use Database\Factories\CurrencyFactory;
use Database\Factories\ProductFactory;
use Tests\Concerns\RefreshesDedicatedDatabase;
use Tests\TestCase;

class ProductPriceApiTest extends TestCase
{
    use RefreshesDedicatedDatabase;

    public function test_additional_prices_can_be_created_and_listed_with_currency_data(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $priceCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);

        $created = $this->postJson("/api/products/{$product->id}/prices", [
            'currency_id' => $priceCurrency->id,
            'price' => '39.95',
        ])
            ->assertCreated()
            ->assertJsonPath('product_id', $product->id)
            ->assertJsonPath('currency_id', $priceCurrency->id)
            ->assertJsonPath('price', '39.95')
            ->assertJsonPath('currency.id', $priceCurrency->id);

        $priceId = $created->json('id');

        $this->getJson("/api/products/{$product->id}/prices")
            ->assertOk()
            ->assertJsonStructure([
                ['id', 'product_id', 'currency_id', 'price', 'currency' => ['id', 'name', 'symbol']],
            ])
            ->assertJsonPath('0.id', $priceId)
            ->assertJsonPath('0.currency.id', $priceCurrency->id);

        $this->assertDatabaseHas('product_prices', [
            'id' => $priceId,
            'product_id' => $product->id,
            'currency_id' => $priceCurrency->id,
            'price' => '39.95',
        ]);
    }

    public function test_additional_price_creation_rejects_missing_invalid_and_out_of_range_values(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $priceCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);
        $validPayload = [
            'currency_id' => $priceCurrency->id,
            'price' => '12.34',
        ];

        foreach (array_keys($validPayload) as $field) {
            $payload = $validPayload;
            unset($payload[$field]);

            $this->postJson("/api/products/{$product->id}/prices", $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        foreach ([
            ['currency_id' => 'not-an-id'],
            ['currency_id' => 999999],
            ['price' => 'not-a-number'],
            ['price' => -0.01],
            ['price' => '1.234'],
            ['price' => '10000000000000.00'],
        ] as $invalidFields) {
            $this->postJson(
                "/api/products/{$product->id}/prices",
                array_merge($validPayload, $invalidFields)
            )
                ->assertUnprocessable();
        }

        $this->assertDatabaseCount('product_prices', 0);
    }

    public function test_additional_price_rejects_the_base_currency_and_duplicate_currency(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $additionalCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);

        $this->postJson("/api/products/{$product->id}/prices", [
            'currency_id' => $baseCurrency->id,
            'price' => 10,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');

        $this->postJson("/api/products/{$product->id}/prices", [
            'currency_id' => $additionalCurrency->id,
            'price' => 10,
        ])
            ->assertCreated();

        $this->postJson("/api/products/{$product->id}/prices", [
            'currency_id' => $additionalCurrency->id,
            'price' => 11,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');

        $this->assertSame(
            1,
            ProductPrice::query()->where('product_id', $product->id)->count()
        );
    }

    public function test_product_ids_are_validated_and_nonexistent_products_return_not_found(): void
    {
        $currency = CurrencyFactory::new()->create();

        $this->getJson('/api/products/invalid-id/prices')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->postJson('/api/products/invalid-id/prices', [
            'currency_id' => 1,
            'price' => 10,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->getJson('/api/products/999999/prices')
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.');

        $this->postJson('/api/products/999999/prices', [
            'currency_id' => $currency->id,
            'price' => 10,
        ])
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_existing_prices_are_related_to_their_product_and_currency(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $priceCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);
        $price = ProductPrice::query()->create([
            'product_id' => $product->id,
            'currency_id' => $priceCurrency->id,
            'price' => 7.25,
        ]);

        $response = $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('currency.id', $baseCurrency->id)
            ->assertJsonPath('prices.0.id', $price->id)
            ->assertJsonPath('prices.0.currency.id', $priceCurrency->id);

        $this->assertSame($product->id, $price->product->id);
        $this->assertSame($priceCurrency->id, $price->currency->id);
    }
}
