<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

use App\Models\Product;

class StoreProductPriceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_id' => $this->route('product'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
       return [
            'product_id' => [
                'required',
                'integer',
                'min:1',
            ],
            'currency_id' => [
               'bail',
                'required',
                'integer',
                'exists:currencies,id',

                Rule::unique('product_prices', 'currency_id')
                    ->where('product_id', $this->route('product')),

                function ($attribute, $value, $fail) {
                    $isBaseCurrency = Product::query()
                        ->whereKey($this->route('product'))
                        ->where('currency_id', $value)
                        ->exists();

                    if ($isBaseCurrency) {
                        $fail(
                            'The currency must be different from the product base currency.'
                        );
                    }
                },
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
                'max:9999999999999.99',
                'decimal:0,2',
            ],
        ];
    }
}
