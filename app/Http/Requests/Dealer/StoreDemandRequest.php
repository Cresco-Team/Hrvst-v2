<?php

namespace App\Http\Requests\Dealer;

use App\Enums\PostTimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer');
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['required', 'integer', 'exists:vegetables,id'],
            'scheduled_at' => ['required', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variety_id' => ['required', 'integer', 'exists:varieties,id'],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'items.*.time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Vegetable is required.',
            'vegetable_id.exists' => 'Selected vegetable does not exist.',
            'scheduled_at.required' => 'Transaction date is required.',
            'scheduled_at.after' => 'Transaction date must be in the future.',
            'scheduled_at.before' => 'Transaction date cannot be more than 3 months away.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.variety_id.required' => 'Each item must have a variety.',
            'items.*.variety_id.exists' => 'Selected variety does not exist.',
            'items.*.quantity_kg.required' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity is too low.',
            'items.*.unit_price.min' => 'Price cannot be negative.',
            'items.*.time_slot.required' => 'Each item must have a time slot.',
        ];
    }
}
