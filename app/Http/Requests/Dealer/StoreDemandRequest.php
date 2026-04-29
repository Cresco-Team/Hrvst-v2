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
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'scheduled_date' => ['required', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Vegetable is required.',
            'vegetable_id.exists' => 'Selected vegetable does not exist.',
            'quantity_kg.required' => 'Quantity is required.',
            'quantity_kg.numeric' => 'Quantity must be a number.',
            'quantity_kg.min' => 'Quantity is too low.',
            'quantity_kg.max' => 'Quantity is too high.',
            'scheduled_date.required' => 'Transaction date is required.',
            'scheduled_date.date' => 'Transaction date must be a valid date.',
            'scheduled_date.after' => 'Transaction date must be in the future.',
            'scheduled_date.before' => 'Transaction date cannot be more than 3 months away.',
            'time_slot.required' => 'A preferred time slot is required.',
            'time_slot.enum' => 'Time slot must be morning, afternoon, or evening.',
        ];
    }
}
