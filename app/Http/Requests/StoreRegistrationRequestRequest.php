<?php

namespace App\Http\Requests;

use App\Enums\RegistrationRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRegistrationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
                Rule::unique('registration_requests', 'phone_number')
                    ->where('status', RegistrationRequestStatus::Pending->value),
            ],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['farmer', 'dealer'])],

            'pin' => ['required', 'string', 'digits:6', 'confirmed'],
            'pin_confirmation' => ['required', 'string', 'digits:6'],

            'municipality_id' => ['nullable', 'required_if:role,farmer', 'integer', Rule::exists('municipalities', 'id')],
            'barangay_id' => ['nullable', 'required_if:role,farmer', 'integer', Rule::exists('barangays', 'id')],
            'latitude' => ['nullable', 'required_if:role,farmer', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:role,farmer', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be a valid PH mobile number (e.g. 09171234567).',
            'phone_number.unique' => 'This phone number already has an account or a pending request.',
            'pin.confirmed' => 'PIN confirmation does not match.',
        ];
    }
}
