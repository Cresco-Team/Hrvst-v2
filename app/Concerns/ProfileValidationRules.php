<?php

namespace App\Concerns;

use App\Models\User;
use Illuminate\Validation\Rule;

trait ProfileValidationRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function profileRules(?int $userId = null): array
    {
        return [
            'name' => $this->nameRules(),
            'email' => $this->emailRules($userId),
            'phone_number' => $this->phoneNumberRules($userId),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function nameRules(): array
    {
        return ['required', 'string', 'max:255'];
    }

    /**
     * Email is optional — phone_number is the primary identifier.
     *
     * @return array<int, mixed>
     */
    protected function emailRules(?int $userId = null): array
    {
        return [
            'nullable',
            'string',
            'email',
            'max:255',
            $userId === null
                ? Rule::unique(User::class)->whereNotNull('email')
                : Rule::unique(User::class)->ignore($userId)->whereNotNull('email'),
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function phoneNumberRules(?int $userId = null): array
    {
        return [
            'required',
            'string',
            'regex:/^(09|\+639)\d{9}$/',
            $userId === null
                ? Rule::unique(User::class, 'phone_number')
                : Rule::unique(User::class, 'phone_number')->ignore($userId),
        ];
    }
}
