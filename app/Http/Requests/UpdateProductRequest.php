<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ProductPrice;

class UpdateProductRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'required',
                'string',
                'max:2000',
            ],
            'price' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
                'max:9999999999999.99',
            ],
            'currency_id' => [
                'bail',
                'sometimes',
                'required',
                'integer',
                'exists:currencies,id',
                function ($attribute, $value, $fail) {
                    $exists = ProductPrice::query()
                        ->where('product_id', $this->route('product'))
                        ->where('currency_id', $value)
                        ->exists();

                    if ($exists) {
                        $fail(
                            'The base currency cannot match an existing additional price currency.'
                        );
                    }
                },
            ],
            'tax_cost' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
                'max:9999999999999.99',
            ],
            'manufacturing_cost' => [
                'sometimes',
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
                'max:9999999999999.99',
            ],
        ];
    }
}
