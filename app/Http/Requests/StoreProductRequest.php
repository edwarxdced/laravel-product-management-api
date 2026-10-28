<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {

        $price_numeric_rules = [
            'required',
            'numeric',
            'min:0',
            'max:9999999999999.99',
            'decimal:0,2'
        ];
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'currency_id' => 'required|integer|exists:currencies,id',
            'price' => $price_numeric_rules,
            'tax_cost' => $price_numeric_rules,
            'manufacturing_cost' => $price_numeric_rules,
        ];
    }
}
