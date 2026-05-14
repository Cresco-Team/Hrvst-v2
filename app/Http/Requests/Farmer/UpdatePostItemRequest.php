<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer');
    }

    public function rules(): array
    {
        $vegetableId = $this->route('postItem')->post->vegetable_id;

        return [
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_kg.required' => 'Quantity is required.',
            'quantity_kg.min' => 'Quantity is too low.',
            'unit_price.min' => 'Price cannot be negative.',
        ];
    }
}
