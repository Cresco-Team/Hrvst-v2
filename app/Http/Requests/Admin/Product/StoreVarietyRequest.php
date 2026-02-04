<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreVarietyRequest extends FormRequest
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
            'image' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'], // 5MB
            'weeks_to_harvest' => ['required', 'integer', 'min:1', 'max:52'],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Please select a parent vegetable.',
            'vegetable_id.exists' => 'Selected vegetable does not exist.',
            'name.required' => 'Variety name is required.',
            'name.max' => 'Variety name must not exceed 255 characters.',
            'image.required' => 'Please upload an image for this variety.',
            'image.image' => 'File must be an image.',
            'image.mimes' => 'Image must be a JPEG, PNG, JPG, or WebP file.',
            'image.max' => 'Image must not exceed 5MB.',
            'weeks_to_harvest.required' => 'Weeks to harvest is required.',
            'weeks_to_harvest.integer' => 'Weeks to harvest must be a number.',
            'weeks_to_harvest.min' => 'Weeks to harvest must be at least 1.',
            'weeks_to_harvest.max' => 'Weeks to harvest must not exceed 52.',
        ];
    }
}