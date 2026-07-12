<?php

namespace App\Actions\Billing;

use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use App\Models\User;

final class CancelSubscriptionAction
{
    public function handle(User $user, SubscriptionFeature $feature): void
    {
        Subscription::active()->for($user, $feature)->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
