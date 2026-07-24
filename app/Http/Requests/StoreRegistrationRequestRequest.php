<?php

namespace App\Http\Requests;

use App\Enums\RegistrationRequestStatus;
use App\Enums\ValidIdType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRegistrationRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'id_type' => $this->id_type ?: null,
            'id_number' => $this->id_number ?: null,
        ]);
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

            'id_type' => ['nullable', Rule::enum(ValidIdType::class)],
            'id_number' => ['nullable', 'required_with:id_type', 'string', 'max:32'],
            'supporting_document' => ['nullable', 'file', 'mimes:jpeg,jpg,png,webp,pdf', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be a valid PH mobile number (e.g. 09171234567).',
            'phone_number.unique' => 'This phone number already has an account or a pending request.',
            'pin.confirmed' => 'PIN confirmation does not match.',
            'id_number.required_with' => 'Please provide the ID number for the selected ID type.',
            'supporting_document.mimes' => 'Document must be a JPEG, PNG, WebP, or PDF file.',
            'supporting_document.max' => 'Document must not exceed 5MB.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->filled('id_type') || ! $this->filled('id_number')) {
                    return;
                }

                $type = ValidIdType::tryFrom($this->input('id_type'));

                if ($type && ! preg_match($type->regex(), strtoupper((string) $this->input('id_number')))) {
                    $validator->errors()->add(
                        'id_number',
                        "ID number format looks invalid for {$type->label()}. {$type->formatHint()}."
                    );
                }
            },
        ];
    }
}
