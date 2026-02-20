<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class StoreDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->dealerProfile?->is_approved ?? false;
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['required', 'exists:varieties,id'],
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'price_offered' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'transaction_date' => ['required', 'date', 'after:today', 'before:' . now()->addMonths(3)->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Variety is required.',
            'quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
            'price_offered.max' => 'Price cannot exceed ₱9,999.99.',
            'transaction_date.after' => 'Expiration must be in the future.',
            'transaction_date.before' => 'Expiration cannot be more than 3 months away.',
        ];
    }
}
