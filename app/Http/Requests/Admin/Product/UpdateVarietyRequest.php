<?php

namespace App\Http\Requests\Admin\Product;

use App\Models\Product\Variety;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin') ?? false;
    }

    public function variety(): Variety
    {
        return $this->route('variety');
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
                'max:255',
                Rule::unique('varieties')
                    ->where('vegetable_id', $this->vegetable_id)
                    ->ignore($this->variety()->id),
            ],
            'image_path' => [
                'nullable',
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

    public function messages(): array
    {
        return [
            'name.unique' => 'A variety with that name already exists for this vegetable.',
        ];
    }
}