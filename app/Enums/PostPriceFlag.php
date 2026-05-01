<?php

namespace App\Enums;

enum PostPriceFlag: string
{
    case Low = 'Low';
    case Fair = 'Fair';
    case High = 'High';

    public static function fromMarketPrice(float $offered, ?object $market): self
    {
        if (! $market) {
            return self::Fair;
        }

        return match (true) {
            $offered < (float) $market->price_min => self::Low,
            $offered > (float) $market->price_max => self::High,
            default => self::Fair,
        };
    }
}
