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
            && $this->user()->farmerProfile?->is_approved;
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['sometimes', 'integer', 'exists:varieties,id'],
            'quantity_kg' => ['sometimes', 'numeric', 'min:0.1', 'max:99999'],
            'offered_price' => ['sometimes', 'numeric', 'min:0', 'max:9999.99'],
            'scheduled_date' => ['sometimes', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'time_slot' => ['sometimes', Rule::enum(PostTimeSlot::class)],
            'image' => ['sometimes', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'quantity_kg.numeric' => 'Quantity must be a number.',
            'quantity_kg.min' => 'Quantity is too low.',
            'quantity_kg.max' => 'Quantity is too high.',
            'offered_price.numeric' => 'Price must be a number.',
            'offered_price.min' => 'Price is too low.',
            'offered_price.max' => 'Price is too high.',
            'scheduled_date.date' => 'Availability date must be a valid date.',
            'scheduled_date.after' => 'Availability date must be in the future.',
            'scheduled_date.before' => 'Availability date cannot be more than 3 months away.',
            'time_slot.required' => 'A preferred time slot is required.',
            'time_slot.enum' => 'Time slot must be morning, afternoon, or evening.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP.',
            'image.max' => 'Image cannot exceed 5 MB.',
        ];
    }
}
