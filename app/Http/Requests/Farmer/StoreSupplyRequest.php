<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostTimeSlot;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Post::class, PostType::Supply]);
    }

    public function rules(): array
    {
        return [
            'scheduled_date' => ['required', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.vegetable_id' => ['required', 'integer', 'exists:vegetables,id'],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required' => 'Scheduled delivery date is required.',
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'scheduled_date.before' => 'Scheduled date cannot be more than 3 months away.',
            'time_slot.required' => 'A preferred time slot is required.',
            'items.required' => 'At least one supply item is required.',
            'items.min' => 'At least one supply item is required.',
            'items.*.vegetable_id.required' => 'Each item must have a vegetable.',
            'items.*.vegetable_id.exists' => 'Selected vegetable does not exist.',
            'items.*.quantity_kg.required' => 'Each item must have a kilogram.',
            'items.*.quantity_kg.min' => 'Kilogram must be at least 0.1 kg.',
            'items.*.quantity_kg.max' => 'Kilogram should not exceed 99,999.',
        ];
    }
}
