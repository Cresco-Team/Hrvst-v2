<?php

namespace App\Http\Requests\Admin\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['required', 'exists:vegetables,id'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Please select a parent vegetable.',
            'vegetable_id.exists' => 'The selected vegetable does not exist.',
            'name.required' => 'Variety name is required.',
        ];
    }
}
