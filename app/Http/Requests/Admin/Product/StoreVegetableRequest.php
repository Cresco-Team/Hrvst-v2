<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVegetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user->hasRole('admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required', 
                'integer', 
                Rule::exists('categories', 'id')
            ],
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('vegetables')->where('category_id', $this->category_id),
            ]
        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'A vegetable with that name already exists in the selected category.',
        ];
    }
}
