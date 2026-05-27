<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['required', 'exists:vegetables,id'],
            'name' => ['required', 'string', 'max:255'],
            'price_min' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'price_max' => ['required', 'numeric', 'min:0', 'max:9999.99', 'gte:price_min'],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Please select a parent vegetable.',
            'vegetable_id.exists' => 'The selected vegetable does not exist.',
            'name.required' => 'Variety name is required.',
            'price_min.required' => 'Minimum price is required.',
            'price_min.numeric' => 'Minimum price must be a number.',
            'price_min.min' => 'Price cannot be negative.',
            'price_min.max' => 'Price cannot exceed ₱9,999.99.',
            'price_max.required' => 'Maximum price is required.',
            'price_max.numeric' => 'Maximum price must be a number.',
            'price_max.min' => 'Price cannot be negative.',
            'price_max.max' => 'Price cannot exceed ₱9,999.99.',
            'price_max.gte' => 'Maximum price must be greater than or equal to minimum price.',
        ];
    }
}
