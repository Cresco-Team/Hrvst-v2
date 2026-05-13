<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer');
    }

    public function rules(): array
    {
        $vegetableId = $this->route('postItem')->post->vegetable_id;

        return [
            'variety_id' => ['required', 'integer', Rule::exists('varieties', 'id')->where('vegetable_id', $vegetableId)],
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Variety is required.',
            'variety_id.exists' => 'Selected variety does not belong to this vegetable.',
            'quantity_kg.required' => 'Quantity is required.',
            'quantity_kg.min' => 'Quantity is too low.',
            'unit_price.min' => 'Price cannot be negative.',
        ];
    }
}
