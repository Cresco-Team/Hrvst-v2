<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product\Vegetable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVegetableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin') ?? false;
    }

    /** @return Vegetable The resolved route model. */
    public function vegetable(): Vegetable
    {
        return $this->route('vegetable');
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
                'max:255',
                Rule::unique('vegetables')
                    ->where('category_id', $this->category_id)
                    ->ignore($this->vegetable()->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'A vegetable with that name already exists in the selected category.',
        ];
    }
}
