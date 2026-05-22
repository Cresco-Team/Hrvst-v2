<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostTimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HarvestSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer')
            && $this->user()->can('harvest', $this->route('supply'));
    }

    public function rules(): array
    {
        $vegetableId = $this->route('supply')?->vegetable_id;

        return [
            'scheduled_date' => ['required', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variety_id' => [
                'required',
                'integer',
                Rule::exists('varieties', 'id')->where('vegetable_id', $vegetableId),
            ],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required' => 'Scheduled delivery date is required.',
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'scheduled_date.before' => 'Scheduled date cannot be more than 3 months away.',
            'time_slot.required' => 'A preferred time slot is required.',
            'time_slot.enum' => 'Time slot must be morning, afternoon, or evening.',
            'items.required' => 'At least one harvest item is required.',
            'items.min' => 'At least one harvest item is required.',
            'items.*.variety_id.required' => 'Each item must have a variety.',
            'items.*.variety_id.exists' => 'Selected variety does not belong to this vegetable.',
            'items.*.quantity_kg.required' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity is too low.',
            'items.*.unit_price.required' => 'Each item must have a price.',
            'items.*.unit_price.min' => 'Price cannot be negative.',
        ];
    }
}
