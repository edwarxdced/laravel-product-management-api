<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\ProductPrice;
use Database\Factories\CurrencyFactory;
use Database\Factories\ProductFactory;
use Illuminate\Database\QueryException;
use Tests\Concerns\RefreshesDedicatedDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshesDedicatedDatabase;

    public function test_products_can_be_created_listed_shown_partially_updated_and_deleted(): void
    {
        $currency = CurrencyFactory::new()->create();
        $payload = $this->validProductPayload($currency);

        $created = $this->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonPath('name', $payload['name'])
            ->assertJsonPath('currency_id', $currency->id);

        $productId = $created->json('id');

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [['id', 'name', 'description', 'price', 'currency_id']],
                'links',
                'current_page',
                'per_page',
                'total',
            ])
            ->assertJsonPath('data.0.id', $productId);

        $this->getJson("/api/products/{$productId}")
            ->assertOk()
            ->assertJsonPath('id', $productId)
            ->assertJsonPath('currency.id', $currency->id)
            ->assertJsonPath('prices', []);

        $updated = $this->patchJson("/api/products/{$productId}", [
            'name' => 'Updated product name',
        ])
            ->assertOk()
            ->assertJsonPath('name', 'Updated product name')
            ->assertJsonPath('description', $payload['description']);

        $this->assertEquals(19.99, $updated->json('price'));

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'name' => 'Updated product name',
            'description' => $payload['description'],
        ]);

        $this->deleteJson("/api/products/{$productId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('products', ['id' => $productId]);
        $this->getJson("/api/products/{$productId}")
            ->assertNotFound()
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_product_creation_accepts_zero_and_the_maximum_supported_decimal_value(): void
    {
        $currency = CurrencyFactory::new()->create();
        $payload = $this->validProductPayload($currency);
        $payload['price'] = '9999999999999.99';
        $payload['tax_cost'] = 0;
        $payload['manufacturing_cost'] = '0.00';

        $response = $this->postJson('/api/products', $payload)
            ->assertCreated()
            ->assertJsonPath('price', '9999999999999.99');

        $this->assertEquals(0, $response->json('tax_cost'));
        $this->assertEquals(0, $response->json('manufacturing_cost'));
    }

    public function test_product_creation_rejects_missing_invalid_and_out_of_range_fields(): void
    {
        $currency = CurrencyFactory::new()->create();
        $validPayload = $this->validProductPayload($currency);

        foreach (array_keys($validPayload) as $field) {
            $payload = $validPayload;
            unset($payload[$field]);

            $this->postJson('/api/products', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($field);
        }

        $invalidValues = [
            'name' => [[], str_repeat('n', 256)],
            'description' => [[], str_repeat('d', 2001)],
            'currency_id' => ['not-an-id', 999999],
            'price' => ['not-a-number', -0.01, '1.234', '10000000000000.00'],
            'tax_cost' => ['not-a-number', -0.01, '1.234', '10000000000000.00'],
            'manufacturing_cost' => ['not-a-number', -0.01, '1.234', '10000000000000.00'],
        ];

        foreach ($invalidValues as $field => $values) {
            foreach ($values as $value) {
                $payload = $validPayload;
                $payload[$field] = $value;

                $this->postJson('/api/products', $payload)
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors($field);
            }
        }
    }

    public function test_product_updates_are_partial_and_validate_supplied_fields(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $newCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);

        $this->patchJson("/api/products/{$product->id}", [
            'price' => '25.50',
            'tax_cost' => 0,
            'manufacturing_cost' => '1.25',
        ])
            ->assertOk()
            ->assertJsonPath('price', '25.50')
            ->assertJsonPath('name', $product->name);

        foreach ([
            'name' => [''],
            'description' => [str_repeat('d', 2001)],
            'price' => [-1, '1.234', '10000000000000.00', 'not-a-number'],
            'tax_cost' => [-1, '1.234', '10000000000000.00', 'not-a-number'],
            'manufacturing_cost' => [-1, '1.234', '10000000000000.00', 'not-a-number'],
            'currency_id' => ['not-an-id', 999999],
        ] as $field => $values) {
            foreach ($values as $value) {
                $this->patchJson("/api/products/{$product->id}", [$field => $value])
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors($field);
            }
        }

        $this->patchJson("/api/products/{$product->id}", [
            'currency_id' => $newCurrency->id,
        ])
            ->assertOk()
            ->assertJsonPath('currency_id', $newCurrency->id);
    }

    public function test_a_base_currency_cannot_be_changed_to_an_existing_additional_price_currency(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $additionalCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);
        ProductPrice::query()->create([
            'product_id' => $product->id,
            'currency_id' => $additionalCurrency->id,
            'price' => 21.00,
        ]);

        $this->patchJson("/api/products/{$product->id}", [
            'currency_id' => $additionalCurrency->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('currency_id');
    }

    public function test_product_ids_are_validated_and_missing_products_return_not_found(): void
    {
        $invalidId = 'invalid-id';
        $missingId = 999999;

        $this->getJson("/api/products/{$invalidId}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->patchJson("/api/products/{$invalidId}", ['name' => 'Updated'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        $this->deleteJson("/api/products/{$invalidId}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('product_id');

        foreach ([
            $this->getJson("/api/products/{$missingId}"),
            $this->patchJson("/api/products/{$missingId}", ['name' => 'Updated']),
            $this->deleteJson("/api/products/{$missingId}"),
        ] as $response) {
            $response->assertNotFound()
                ->assertJsonPath('message', 'Resource not found.');
        }
    }

    public function test_product_listing_paginates_with_expected_defaults_and_limits(): void
    {
        $currency = CurrencyFactory::new()->create();
        ProductFactory::new()->count(17)->create([
            'currency_id' => $currency->id,
        ]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonPath('per_page', 15)
            ->assertJsonPath('current_page', 1)
            ->assertJsonCount(15, 'data');

        $this->getJson('/api/products?per_page=5&page=2')
            ->assertOk()
            ->assertJsonPath('per_page', 5)
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data');

        $this->getJson('/api/products?per_page=100')
            ->assertOk()
            ->assertJsonPath('per_page', 100)
            ->assertJsonCount(17, 'data');

        foreach (['per_page=0', 'per_page=101', 'per_page=abc', 'page=0', 'page=-1', 'page=abc'] as $query) {
            $this->getJson("/api/products?{$query}")
                ->assertUnprocessable();
        }
    }

    public function test_product_relations_and_database_foreign_keys_are_enforced(): void
    {
        $currency = CurrencyFactory::new()->create();
        $otherCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $currency->id,
        ]);
        $additionalPrice = ProductPrice::query()->create([
            'product_id' => $product->id,
            'currency_id' => $otherCurrency->id,
            'price' => 29.99,
        ]);

        $this->assertInstanceOf(Currency::class, $product->currency);
        $this->assertTrue($product->prices->contains($additionalPrice));
        $this->assertSame($product->id, $additionalPrice->product->id);
        $this->assertSame($otherCurrency->id, $additionalPrice->currency->id);
        $this->assertSame(1, $currency->products()->count());
        $this->assertSame(1, $otherCurrency->productPrices()->count());

        $this->expectException(QueryException::class);
        ProductPrice::query()->create([
            'product_id' => $product->id,
            'currency_id' => 999999,
            'price' => 1.00,
        ]);
    }

    public function test_deleting_a_product_cascades_to_its_additional_prices_and_currency_delete_is_restricted(): void
    {
        $baseCurrency = CurrencyFactory::new()->create();
        $priceCurrency = CurrencyFactory::new()->create();
        $product = ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);
        $price = ProductPrice::query()->create([
            'product_id' => $product->id,
            'currency_id' => $priceCurrency->id,
            'price' => 12.50,
        ]);

        $this->deleteJson("/api/products/{$product->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('product_prices', ['id' => $price->id]);

        ProductFactory::new()->create([
            'currency_id' => $baseCurrency->id,
        ]);

        $this->expectException(QueryException::class);
        $baseCurrency->delete();
    }

    private function validProductPayload(Currency $currency): array
    {
        return [
            'name' => 'Test product',
            'description' => 'Product description',
            'currency_id' => $currency->id,
            'price' => '19.99',
            'tax_cost' => '2.50',
            'manufacturing_cost' => '8.25',
        ];
    }
}
