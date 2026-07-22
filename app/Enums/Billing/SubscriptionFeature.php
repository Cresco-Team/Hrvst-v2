<?php

namespace App\Enums\Billing;

use App\Models\User;

enum SubscriptionFeature: string
{
    case AdminAnalytics = 'admin_analytics';       // LTVTP institutional license
    case FarmerForecasts = 'farmer_forecasts';     // premium demand forecasting for farmers
    case DealerMarketIntel = 'dealer_market_intel'; // premium supply/imbalance intel for dealers

    public function label(): string
    {
        return match ($this) {
            self::AdminAnalytics => 'Platform Analytics License',
            self::FarmerForecasts => 'Premium Demand Forecasts',
            self::DealerMarketIntel => 'Premium Market Intelligence',
        };
    }

    /** Which role is allowed to purchase this feature. */
    public function role(): string
    {
        return match ($this) {
            self::AdminAnalytics => 'admin',
            self::FarmerForecasts => 'farmer',
            self::DealerMarketIntel => 'dealer',
        };
    }

    public static function forUser(User $user): ?self
    {
        return match (true) {
            $user->hasRole('admin') => self::AdminAnalytics,
            $user->hasRole('farmer') => self::FarmerForecasts,
            $user->hasRole('dealer') => self::DealerMarketIntel,
            default => null,
        };
    }
}
