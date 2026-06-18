<?php

namespace App\Http\Requests\Farmer;

use App\Enums\PostType;
use App\Models\Marketplace\Post;
use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Post::class, PostType::Supply]);
    }

    public function rules(): array
    {
        return [
            'vegetable_id' => ['required', 'integer', 'exists:vegetables,id'],
            'expected_harvest_month' => ['required', 'string', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/', 'after_or_equal:'.now()->format('Y-m')],
            'estimated_total_weight' => ['required', 'numeric', 'min:0.1', 'max:999999'],
        ];
    }

    public function messages(): array
    {
        return [
            'vegetable_id.required' => 'Vegetable is required.',
            'vegetable_id.exists' => 'Selected vegetable does not exist.',
            'expected_harvest_month.required' => 'Expected harvest month is required.',
            'expected_harvest_month.regex' => 'Expected Harvest Month must be in YYYY-MM format.',
            'expected_harvest_month.after_or_equal' => 'Expected Harvest Month cannot be in the past.',
            'estimated_total_weight.required' => 'Estimated weight is required.',
            'estimated_total_weight.min' => 'Estimated weight is too low.',
        ];
    }
}
