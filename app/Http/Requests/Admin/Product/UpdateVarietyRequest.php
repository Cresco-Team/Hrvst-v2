<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['required', 'exists:vegetables,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('varieties', 'name')
                    ->where('vegetable_id', $this->integer('vegetable_id'))
                    ->ignore($this->route('variety')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Please select a parent vegetable.',
            'vegetable_id.exists' => 'The selected vegetable does not exist.',
            'name.required' => 'Variety name is required.',
            'name.unique' => 'A variety with this name already exists for the selected vegetable.',
        ];
    }
}
