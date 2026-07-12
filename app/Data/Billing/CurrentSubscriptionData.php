<?php

namespace App\Data\Billing;

use App\Models\Billing\Subscription;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CurrentSubscriptionData extends Data
{
    public function __construct(
        public ?string $plan,
        public ?string $status,
        public bool $is_active,
        public ?string $ends_at,
        public ?string $ends_at_human,
    ) {}

    public static function fromModel(?Subscription $subscription): self
    {
        if ($subscription === null) {
            return new self(null, null, false, null, null);
        }

        return new self(
            plan: $subscription->plan->value,
            status: $subscription->status->value,
            is_active: $subscription->isActive() ?? $subscription->ends_at?->isFuture() ?? false,
            ends_at: $subscription->ends_at?->format('M j, Y'),
            ends_at_human: $subscription->ends_at?->diffForHumans(),
        );
    }
}
