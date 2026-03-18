<?php

namespace App\Http\Requests\Dealer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer')
            && $this->user()->dealerProfile?->is_approved;
    }

    public function rules(): array
    {
        return [
            'variety_id'     => ['sometimes', 'integer', 'exists:varieties,id'],
            'quantity_kg'    => ['sometimes', 'numeric', 'min:0.01', 'max:99999'],
            'offered_price'  => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:9999.99'],
            'scheduled_date' => ['sometimes', 'nullable', 'date', 'after:today', 'before:' . now()->addMonths(3)->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_kg.numeric'      => 'Quantity must be a number.',
            'quantity_kg.min'          => 'Quantity is too low.',
            'offered_price.numeric'    => 'Budget must be a number.',
            'offered_price.min'        => 'Budget is too low.',
            'scheduled_date.date'      => 'Transaction date must be a valid date.',
            'scheduled_date.after'     => 'Transaction date must be in the future.',
            'scheduled_date.before'    => 'Transaction date cannot be more than 3 months away.',
        ];
    }
}
