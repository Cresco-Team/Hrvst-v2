<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostPriceFlag;
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
            'variety_id'    => ['required', 'integer', 'exists:varieties,id'],
            'quantity_kg'   => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'offered_price' => ['numeric', 'min:0', 'max:9999.99'],
            'price_flag'    => [Rule::enum(PostPriceFlag::class)],

            'expiration_date'   => ['required', 'date', 'after:today', 'before:' . now()->addMonths(3)->toDateString()],
            'image'             => ['image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'variety_id.required'   => 'Variety is required.',

            'quantity_kg.required'  => 'Quantity is required',
            'quantity_kg.numeric'   => 'Quantity must be a number',
            'quantity_kg.min'       => 'Quantity is too low',
            'quantity_kg.max'       => 'Quantity is too high',

            'offered_price.numeric' => 'Price must be a number',
            'offered_price.min'     => 'Price is too low',
            'offered_price.max'     => 'Price is too high',

            'expiration_date.required'  => 'Expiration date is required',
            'expiration_date.date'      => 'Expiration date must be a vald date',
            'expiration_date.after'     => 'Expiration date must be in the future',
            'expiration_date.before'    => 'Expiration date cannot be more than 3 months away',

            'image.mimes'   => 'Image should be of type jpeg, jpg, png, web',
            'image.max'     => 'Image cannot exceed 5MB',
        ];
    }
}
