<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlantingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $planting = $this->route('planting');
        
        return $this->user()->hasRole('farmer') 
            && $this->user()->can('update', $planting);
    }

    public function rules(): array
    {
        return [
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:99999'],
            'price_asking' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'expiration_date' => ['required', 'date', 'after:today', 'before:' . now()->addMonths(6)->toDateString()],
            'image_path' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120']
        ];
    }

    public function messages(): array
    {
        return [
            'weight_kg.required' => 'Weight is required.',
            'weight_kg.numeric' => 'Weight must be a number.',
            'weight_kg.min' => 'Weight must be at least 0.1 kg.',
            'weight_kg.max' => 'Weight cannot exceed 99,999 kg.',

            'price_asking.numeric' => 'Price must be a number.',
            'price_asking.min' => 'Price must be at least ₱0.00.',
            'price_asking.max' => 'Price cannot exceed ₱999.99.',

            'expiration_date.required' => 'Expected harvest date is required.',
            'expiration_date.date' => 'Expected harvest date must be a valid date.',
            'expiration_date.after' => 'Expected harvest date must be in the future.',
            'expiration_date.before' => 'Expected harvest date cannot be more than 6 months away.',

            'image_path.required' => 'An image of the planting is required.',
            'image_path.image' => 'The uploaded file must be an image.',
            'image_path.mimes' => 'The image must be a file of type: jpeg, jpg, png, webp.',
            'image_path.max' => 'The image cannot exceed 5MB in size.',
        ];
    }
}
