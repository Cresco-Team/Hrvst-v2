<?php

namespace App\Http\Requests\Farmer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('farmer')
            && $this->user()->can('update', $this->route('supply'));
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['sometimes', 'integer', 'exists:vegetables,id'],
            'expected_harvest_month' => ['sometimes', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'after_or_equal:'.now()->format('Y-m')],
            'estimated_total_weight' => ['sometimes', 'numeric', 'min:0.1', 'max:999999'],
            'image' => ['sometimes', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'expected_harvest_month.regex' => 'Expecteed harvest month must be in YYYY-MM format.',
            'expected_harvest_month.after_or_equal' => 'Expected harvest month cannot be in the past.',
            'estimated_total_weight.min' => 'Estimated weight is too low.',
            'image.mimes' => 'Image must be JPEG, PNG, or WebP.',
            'image.max' => 'Image cannot exceed 5 MB.',
        ];
    }
}
