<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer')
            && $this->user()->can('update', $this->route('supply'));
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['sometimes', 'integer', 'exists:vegetables,id'],
            'expected_harvest_month' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'after_or_equal:'.now()->format('Y-m')],
            'estimated_total_weight' => ['nullable', 'numeric', 'min:0.1', 'max:999999'],
            'scheduled_date' => ['sometimes', 'date'],
            'time_slot' => ['sometimes', 'string'],

            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.variety_id' => ['required_with:items', 'integer', 'exists:varieties,id'],
            'items.*.quantity_kg' => ['required_with:items', 'numeric', 'min:0.1'],
        ];
    }

    public function messages(): array
    {
        return [
            'expected_harvest_month.regex' => 'Expected harvest month must be in YYYY-MM format.',
            'expected_harvest_month.after_or_equal' => 'Expected harvest month cannot be in the past.',
            'estimated_total_weight.min' => 'Estimated weight is too low.',
            'items.min' => 'At least one supply item is required.',
            'items.*.variety_id.required_with' => 'Each item must have a variety.',
            'items.*.variety_id.exists' => 'Selected variety does not exist.',
            'items.*.quantity_kg.required_with' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
        ];
    }
}
