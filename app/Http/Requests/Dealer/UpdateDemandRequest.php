<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer') 
            && $this->user()->dealerProfile->is_approved;
    }

    public function rules(): array
    {
        return [
            'variety_id'      => ['sometimes', 'integer', 'exists:varieties,id'],
            'quantity_kg'     => ['sometimes', 'numeric', 'min:0.01'],
            'offered_price'   => ['sometimes', 'numeric', 'min:0'],
            
            'transaction_date' => ['sometimes', 'nullable', 'date', 'after:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.exists'   => 'Variety is required.',

            'quantity_kg.numeric'   => 'Quantity must be a number',
            'quantity_kg.min'       => 'Quantity is too low',
            'quantity_kg.max'       => 'Quantity is too high',

            'offered_price.numeric' => 'Price must be a number',
            'offered_price.min'     => 'Price is too low',
            'offered_price.max'     => 'Price is too high',

            'transaction_date.date'      => 'Expiration date must be a vald date',
            'transaction_date.after'     => 'Expiration date must be in the future',
            'transaction_date.before'    => 'Expiration date cannot be more than 3 months away',
        ];
    }
}
