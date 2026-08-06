<?php

namespace App\Http\Requests\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = $this->profileRules($this->user()->id);

        if ($this->phoneNumberChanging()) {
            $rules['current_password'] = $this->currentPasswordRules();
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be a valid PH mobile number (e.g. 09171234567).',
            'phone_number.unique' => 'This phone number is already registered.',
            'current_password.current_password' => 'Your current password is incorrect.',
        ];
    }

    /**
     * Whether the submitted phone_number differs from the user's current one.
     * Used to conditionally require current_password — phone_number doubles
     * as the login credential and this app has no password-reset fallback
     * (see PasswordResetTest — /forgot-password and /reset-password 404),
     * so changing it silently would be a real account-takeover path.
     */
    private function phoneNumberChanging(): bool
    {
        return $this->filled('phone_number')
            && $this->input('phone_number') !== $this->user()->phone_number;
    }
}
