<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => [
                'required',
                'integer',
                Rule::exists('vegetables', 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('varieties')->where('vegetable_id', $this->vegetable_id),
            ],
            'image_path' => [
                'required',
                'string',
                'max:255',
            ],
            'weeks_to_harvest' => [
                'required',
                'integer',
                'min:1',
                'max:52',
            ],
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'A variety with that name already exists for this vegetable.',
        ];
    }
}