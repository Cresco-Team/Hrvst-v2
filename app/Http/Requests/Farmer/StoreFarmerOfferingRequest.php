<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmerOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->farmerProfile?->is_approved ?? false;
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['required', 'exists:varieties,id'],
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'price_asking' => ['required', 'numeric', 'min:0', 'max:9999.99'],
            'expiration_date' => ['required', 'date', 'after:today', 'before:' . now()->addMonths(3)->toDateString()],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Variety is required.',
            'image.required' => 'Image is required.',
            'image.max' => 'Image cannot exceed 5MB.',
            'quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
            'price_asking.max' => 'Price cannot exceed ₱9,999.99.',
            'expiration_date.after' => 'Expiration must be in the future.',
            'expiration_date.before' => 'Expiration cannot be more than 3 months away.',
        ];
    }
}
