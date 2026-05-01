<?php

namespace App\Http\Requests\Dealer;

use App\Enums\PostTimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDemandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('dealer');
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['sometimes', 'integer', 'exists:vegetables,id'],
            'scheduled_at' => ['sometimes', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.variety_id' => ['required_with:items', 'integer', 'exists:varieties,id'],
            'items.*.quantity_kg' => ['required_with:items', 'numeric', 'min:0.1', 'max:99999'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'items.*.time_slot' => ['required_with:items', Rule::enum(PostTimeSlot::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'Transaction date must be in the future.',
            'scheduled_at.before' => 'Transaction date cannot be more than 3 months away.',
            'items.min' => 'At least one item is required.',
            'items.*.variety_id.required_with' => 'Each item must have a variety.',
            'items.*.quantity_kg.required_with' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity is too low.',
            'items.*.unit_price.min' => 'Price cannot be negative.',
            'items.*.time_slot.required_with' => 'Each item must have a time slot.',
        ];
    }
}
