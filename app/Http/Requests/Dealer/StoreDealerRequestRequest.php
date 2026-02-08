<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class StoreDealerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->dealerProfile?->is_approved ?? false;
    }

    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date', 'after:today'],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.variety_id' => ['required', 'exists:varieties,id'],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'items.*.price_offered' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.after' => 'Transaction date must be in the future.',
            'items.required' => 'At least one variety is required.',
            'items.min' => 'At least one variety is required.',
            'items.max' => 'Maximum 10 varieties per request.',
            'items.*.variety_id.required' => 'Variety is required.',
            'items.*.variety_id.exists' => 'Selected variety does not exist.',
            'items.*.quantity_kg.required' => 'Quantity is required.',
            'items.*.quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
            'items.*.price_offered.required' => 'Price is required.',
            'items.*.price_offered.max' => 'Price cannot exceed ₱9,999.99.',
        ];
    }
}
