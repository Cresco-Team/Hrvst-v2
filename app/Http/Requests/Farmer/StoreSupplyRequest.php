<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostPriceFlag;
use App\Enums\PostTimeSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer')
            && $this->user()->farmerProfile?->is_approved;
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['required', 'integer', 'exists:varieties,id'],
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'offered_price' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'price_flag' => ['sometimes', Rule::enum(PostPriceFlag::class)],
            'scheduled_date' => ['required', 'date', 'after:today', 'before:'.now()->addMonths(3)->toDateString()],
            'time_slot' => ['required', Rule::enum(PostTimeSlot::class)],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Variety is required.',
            'variety_id.exists' => 'Selected variety does not exist.',
            'quantity_kg.required' => 'Quantity is required.',
            'quantity_kg.numeric' => 'Quantity must be a number.',
            'quantity_kg.min' => 'Quantity is too low.',
            'quantity_kg.max' => 'Quantity is too high.',
            'offered_price.required' => 'Price is required.',
            'offered_price.numeric' => 'Price must be a number.',
            'offered_price.min' => 'Price is too low.',
            'offered_price.max' => 'Price is too high.',
            'scheduled_date.required' => 'Availability date is required.',
            'scheduled_date.date' => 'Availability date must be a valid date.',
            'scheduled_date.after' => 'Availability date must be in the future.',
            'scheduled_date.before' => 'Availability date cannot be more than 3 months away.',
            'time_slot.required' => 'A preferred time slot is required.',
            'time_slot.enum' => 'Time slot must be morning, afternoon, or evening.',
            'image.required' => 'An image is required.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP.',
            'image.max' => 'Image cannot exceed 5 MB.',
        ];
    }
}
