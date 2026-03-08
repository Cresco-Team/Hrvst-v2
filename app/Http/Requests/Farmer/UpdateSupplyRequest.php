<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

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
            'title'           => ['nullable', 'string', 'max:255'],
            'variety_id'      => ['sometimes', 'integer', 'exists:varieties,id'],
            'quantity_kg'     => ['sometimes', 'numeric', 'min:0.01'],
            'offered_price'   => ['sometimes', 'numeric', 'min:0'],

            'expiration_date' => ['sometimes', 'nullable', 'date', 'after:today'],
            'image'           => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.max'             => 'Post title is too long',
            'variety_id.exists'   => 'Variety is required.',

            'quantity_kg.numeric'   => 'Quantity must be a number',
            'quantity_kg.min'       => 'Quantity is too low',
            'quantity_kg.max'       => 'Quantity is too high',

            'offered_price.numeric' => 'Price must be a number',
            'offered_price.min'     => 'Price is too low',
            'offered_price.max'     => 'Price is too high',

            'expiration_date.date'      => 'Expiration date must be a vald date',
            'expiration_date.after'     => 'Expiration date must be in the future',
            'expiration_date.before'    => 'Expiration date cannot be more than 3 months away',

            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP format.',
            'image.max' => 'Image size cannot exceed 5MB.',
        ];
    }
}
