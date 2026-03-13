<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['required', 'exists:vegetables,id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // Optional on update
            'weeks_to_harvest' => ['required', 'integer', 'min:1', 'max:52'],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Please select a parent vegetable.',
            'vegetable_id.exists' => 'The selected vegetable does not exist.',
            'name.required' => 'Variety name is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP format.',
            'image.max' => 'Image size cannot exceed 5MB.',
            'weeks_to_harvest.required' => 'Weeks to harvest is required.',
            'weeks_to_harvest.min' => 'Must be at least 1 week.',
            'weeks_to_harvest.max' => 'Cannot exceed 52 weeks.',
        ];
    }
}
