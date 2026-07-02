<?php

namespace App\Http\Requests\Vegetable;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateVegetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'vegetable_name' => [ 'required', 'string', 'max:255'],
            'variety_name' => [
                'nullable', 
                'string', 
                'max:255',
                Rule::unique('vegetables', 'variety_name')->where(function ($query) {
                    return $query->where('vegetable_name', $this->vegetable_name);
                })
            ],
            'local_name' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category does not exist.',
            'vegetable_name.required' => 'Vegetable name is required.',
            'vegetable_name.unique' => 'This vegetable/variety combination already exists in the selected category.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP format.',
            'image.max' => 'Image size cannot exceed 5MB.',
        ];
    }
}
