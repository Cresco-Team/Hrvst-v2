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
            'scheduled_date' => ['required', 'date', 'after:today'],
            'time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variety_id' => ['required', 'integer', 'exists:varieties,id'],
            'items.*.quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
        ];
    }

    public function messages(): array
    {
        return [
            'scheduled_date.required' => 'Scheduled delivery date is required.',
            'scheduled_date.after' => 'Scheduled date must be in the future.',
            'time_slot.required' => 'A preferred time slot is required.',
            'items.required' => 'At least one supply item is required.',
            'items.min' => 'At least one supply item is required.',
            'items.*.variety_id.required' => 'Each item must have a variety.',
            'items.*.variety_id.exists' => 'Selected variety does not exist.',
            'items.*.quantity_kg.required' => 'Each item must have a quantity.',
            'items.*.quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
        ];
    }
}
