<?php

namespace App\Http\Requests\Dealer;

use App\Enums\PostPriceFlag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer')
            && $this->user()->dealerProfile?->is_approved;
    }

    public function rules(): array
    {
        return [
            'variety_id'     => ['required', 'integer', 'exists:varieties,id'],
            'quantity_kg'    => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'offered_price'  => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'price_flag'     => ['sometimes', Rule::enum(PostPriceFlag::class)],
            'scheduled_date' => ['required', 'date', 'after:today', 'before:' . now()->addMonths(3)->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required'      => 'Variety is required.',
            'variety_id.exists'        => 'Selected variety does not exist.',
            'quantity_kg.required'     => 'Quantity is required.',
            'quantity_kg.numeric'      => 'Quantity must be a number.',
            'quantity_kg.min'          => 'Quantity is too low.',
            'quantity_kg.max'          => 'Quantity is too high.',
            'offered_price.numeric'    => 'Budget must be a number.',
            'offered_price.min'        => 'Budget is too low.',
            'offered_price.max'        => 'Budget is too high.',
            'scheduled_date.required'  => 'Transaction date is required.',
            'scheduled_date.date'      => 'Transaction date must be a valid date.',
            'scheduled_date.after'     => 'Transaction date must be in the future.',
            'scheduled_date.before'    => 'Transaction date cannot be more than 3 months away.',
        ];
    }
}
