<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $varietyId = $this->route('variety')?->id;

        return [
            'vegetable_id' => [
                'required',
                'integer',
                Rule::exists('vegetables', 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                // Unique combination of vegetable_id and name, excluding current variety
                Rule::unique('varieties')->where(function ($query) {
                    return $query->where('vegetable_id', $this->vegetable_id);
                })->ignore($varietyId),
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,jpg,png,webp',
                'max:2048',
            ],
            'weeks_to_harvest' => [
                'required',
                'integer',
                'min:1',
                'max:52',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Please select a parent vegetable.',
            'vegetable_id.exists' => 'The selected vegetable does not exist.',
            'name.required' => 'Variety name is required.',
            'name.unique' => 'This variety name already exists for the selected vegetable.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be jpeg, jpg, png, or webp format.',
            'image.max' => 'Image size must not exceed 2MB.',
            'weeks_to_harvest.required' => 'Weeks to harvest is required.',
            'weeks_to_harvest.min' => 'Weeks to harvest must be at least 1 week.',
            'weeks_to_harvest.max' => 'Weeks to harvest cannot exceed 52 weeks.',
        ];
    }

    public function attributes(): array
    {
        return [
            'vegetable_id' => 'parent vegetable',
            'weeks_to_harvest' => 'harvest time',
        ];
    }
}