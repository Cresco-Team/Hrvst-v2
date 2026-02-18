<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDealerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer') 
            && $this->user()->dealerProfile->is_approved;
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['required', 'exists:varieties,id'],
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'price_offered' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'transaction_date' => ['required', 'date', 'after:today', 'before_or_equal:' . now()->addMonths(3)->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Please select a variety.',
            'variety_id.exists' => 'Selected variety does not exist.',
            'quantity_kg.required' => 'Quantity is required.',
            'quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
            'quantity_kg.max' => 'Quantity cannot exceed 99,999 kg.',
            'price_offered.required' => 'Asking price is required.',
            'price_offered.min' => 'Price must be at least ₱0.01.',
            'price_offered.max' => 'Price cannot exceed ₱9,999.99.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.after' => 'Transaction date must be after today.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be more than 1 month from now.',
        ];
    }
}
