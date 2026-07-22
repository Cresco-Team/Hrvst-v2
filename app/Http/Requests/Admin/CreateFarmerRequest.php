<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFarmerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => [
                'required',
                'string',
                'regex:/^(09|\+639)\d{9}$/',
                Rule::unique('users', 'phone_number'),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'province_id' => ['sometimes'],
            'municipality_id' => ['required', 'integer', Rule::exists('municipalities', 'id')],
            'barangay_id' => ['required', 'integer', Rule::exists('barangays', 'id')],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be a valid PH mobile number (e.g. 09171234567).',
            'phone_number.unique' => 'This phone number is already registered.',
        ];
    }
}
