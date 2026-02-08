<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDealerRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        $dealerRequest = $this->route('dealerRequest');
        
        return $this->user()->hasRole('dealer') 
            && $this->user()->can('update', $dealerRequest);
    }

    public function rules(): array
    {
        return [
            'transaction_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'before_or_equal:' . now()->addMonths(3)->format('Y-m-d'),
            ],
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.variety_id' => ['required', 'exists:varieties,id'],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'items.*.price_offered' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.after_or_equal' => 'Transaction date must be today or later.',
            'transaction_date.before_or_equal' => 'Transaction date cannot be more than 3 months in the future.',
            'items.required' => 'At least one variety is required.',
            'items.min' => 'You must request at least one variety.',
            'items.max' => 'You cannot request more than 10 varieties at once.',
            'items.*.variety_id.required' => 'Variety selection is required.',
            'items.*.variety_id.exists' => 'Selected variety does not exist.',
            'items.*.quantity_kg.required' => 'Quantity is required.',
            'items.*.quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
            'items.*.quantity_kg.max' => 'Quantity cannot exceed 99,999 kg.',
            'items.*.price_offered.required' => 'Price offered is required.',
            'items.*.price_offered.min' => 'Price must be at least ₱0.01.',
            'items.*.price_offered.max' => 'Price cannot exceed ₱9,999.99.',
        ];
    }
}
