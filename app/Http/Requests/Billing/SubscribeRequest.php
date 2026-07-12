<?php

namespace App\Http\Requests\Billing;

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $feature = SubscriptionFeature::tryFrom($this->input('feature'));

        return $feature !== null && $this->user()->hasRole($feature->role());
    }

    public function rules(): array
    {
        return [
            'feature' => ['required', Rule::enum(SubscriptionFeature::class)],
            'plan' => ['required', Rule::enum(SubscriptionPlan::class)],
        ];
    }
}
