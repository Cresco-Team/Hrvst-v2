<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pin' => ['required', 'string', 'digits:6', 'confirmed'],
            'pin_confirmation' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'pin.required' => 'A new PIN is required.',
            'pin.digits' => 'PIN must be exactly 6 digits.',
            'pin.confirmed' => 'PIN confirmation does not match.',
            'pin_confirmation.required' => 'Please confirm your new PIN.',
            'pin_confirmation.digits' => 'PIN confirmation must be exactly 6 digits.',
        ];
    }
}
