<?php

namespace App\Enums\Billing;

enum SubscriptionPlan: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annual = 'annual';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annual => 'Annual',
        };
    }

    public function duration(): \DateInterval
    {
        return match ($this) {
            self::Monthly => new \DateInterval('P1M'),
            self::Quarterly => new \DateInterval('P3M'),
            self::Annual => new \DateInterval('P1Y'),
        };
    }

    public function priceCents(SubscriptionFeature $feature): int
    {
        return match ($feature) {
            SubscriptionFeature::AdminAnalytics => match ($this) {
                self::Monthly => 250_000,    // ₱2,500 — institutional license
                self::Quarterly => 675_000,  // ~10% off
                self::Annual => 2_400_000,   // ~20% off
            },
            SubscriptionFeature::FarmerForecasts,
            SubscriptionFeature::DealerMarketIntel => match ($this) {
                self::Monthly => 9_900,      // ₱99 — individual add-on
                self::Quarterly => 26_700,
                self::Annual => 95_000,
            },
        };
    }

    public static function optionsFor(SubscriptionFeature $feature): array
    {
        return array_map(fn (self $p) => [
            'value' => $p->value,
            'label' => $p->label(),
            'price_cents' => $p->priceCents($feature),
        ], self::cases());
    }
}
