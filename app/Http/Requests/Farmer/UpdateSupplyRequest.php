<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostTimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'scheduled_date' => ['sometimes', 'date', 'after:today'],
            'time_slot' => ['sometimes', Rule::enum(PostTimeSlot::class)],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.vegetable_id' => ['required_with:items', 'integer', 'exists:vegetables,id'],
            'items.*.quantity_kg' => ['required_with:items', 'numeric', 'min:0.1'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'items.min' => 'At least one supply item is required.',
            'items.*.vegetable_id.required_with' => 'Each item must have a vegetable.',
            'items.*.vegetable_id.exists' => 'Selected vegetable does not exist.',
            'items.*.quantity_kg.required_with' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
        ];
    }
}
