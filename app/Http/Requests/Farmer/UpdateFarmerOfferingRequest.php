<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFarmerOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $farmerOffering = $this->route('farmerOffering');
        
        return $this->user()->hasRole('farmer') 
            && $this->user()->can('update', $farmerOffering);
    }

    public function rules(): array
    {
        return [
            'variety_id' => ['required', 'exists:varieties,id'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // Optional on update
            'quantity_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'price_asking' => ['required', 'numeric', 'min:0.01', 'max:9999.99'],
            'expiration_date' => [
                'required',
                'date',
                'after:today',
                'before_or_equal:' . now()->addMonths(1)->format('Y-m-d'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required' => 'Please select a variety.',
            'variety_id.exists' => 'Selected variety does not exist.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP format.',
            'image.max' => 'Image size cannot exceed 5MB.',
            'quantity_kg.required' => 'Quantity is required.',
            'quantity_kg.min' => 'Quantity must be at least 0.1 kg.',
            'quantity_kg.max' => 'Quantity cannot exceed 99,999 kg.',
            'price_asking.required' => 'Asking price is required.',
            'price_asking.min' => 'Price must be at least ₱0.01.',
            'price_asking.max' => 'Price cannot exceed ₱9,999.99.',
            'expiration_date.required' => 'Expiration date is required.',
            'expiration_date.after' => 'Expiration date must be after today.',
            'expiration_date.before_or_equal' => 'Expiration date cannot be more than 1 month from now.',
        ];
    }
}
