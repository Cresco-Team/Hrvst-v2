<?php

namespace App\Actions\Billing;

use App\Contracts\Billing\PaymentGateway;
use App\Enums\Billing\SubscriptionFeature;
use App\Enums\Billing\SubscriptionPlan;
use App\Enums\Billing\SubscriptionStatus;
use App\Exceptions\Billing\PaymentFailedException;
use App\Models\Billing\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class SubscribeToPlanAction
{
    public function __construct(private PaymentGateway $gateway) {}

    public function handle(User $user, SubscriptionFeature $feature, SubscriptionPlan $plan): Subscription
    {
        $amount = $plan->priceCents($feature);

        $result = $this->gateway->charge(
            amountCents: $amount,
            currency: 'PHP',
            meta: ['user_id' => $user->id, 'feature' => $feature->value, 'plan' => $plan->value],
        );

        if (! $result->successful) {
            throw new PaymentFailedException($result->message ?? 'Payment declined.');
        }

        return DB::transaction(function () use ($user, $feature, $plan, $amount, $result) {
            Subscription::active()->for($user, $feature)->update([
                'status' => SubscriptionStatus::Expired,
            ]);

            return Subscription::create([
                'user_id' => $user->id,
                'feature' => $feature,
                'plan' => $plan,
                'status' => SubscriptionStatus::Active,
                'amount_cents' => $amount,
                'currency' => 'PHP',
                'payment_gateway' => $result->gateway,
                'payment_reference' => $result->reference,
                'starts_at' => now(),
                'ends_at' => now()->add($plan->duration()),
            ]);
        });
    }
}
