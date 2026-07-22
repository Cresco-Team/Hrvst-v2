<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPhoneRequest extends FormRequest
{
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'phone_number' => $this->phoneNumberRules($this->route('user')->id),
        ];
    }
}
