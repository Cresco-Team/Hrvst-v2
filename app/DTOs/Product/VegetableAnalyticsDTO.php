<?php

namespace App\DTOs\Product;

use App\Enums\Analytics\ImbalanceBand;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableAnalyticsDTO
{
    public function __construct(
        public float $supply_demand_ratio,
        public ImbalanceBand $imbalance_band,
        public ?float $supply_fulfillment_rate,
        public ?float $demand_fulfillment_rate,
        public ?float $supply_volume_mom_pct,
        public ?float $demand_volume_mom_pct,
        /** @var VegetableRecommendationDTO[] */
        public array $recommendations,
        /**
         * Forward-looking replacement for the Market Balance card. Estimated
         * from the same calendar month last year, falling back to the
         * trailing 3-month average when no same-month-last-year data exists.
         *
         * @var array{band: string, explanation: string}
         */
        public array $expected_balance = ['band' => 'balanced', 'explanation' => ''],
    ) {}

    public function toArray(): array
    {
        return [
            'supply_demand_ratio' => $this->supply_demand_ratio,
            'imbalance_band' => $this->imbalance_band->value,
            'supply_fulfillment_rate' => $this->supply_fulfillment_rate,
            'demand_fulfillment_rate' => $this->demand_fulfillment_rate,
            'supply_volume_mom_pct' => $this->supply_volume_mom_pct,
            'demand_volume_mom_pct' => $this->demand_volume_mom_pct,
            'recommendations' => array_map(
                fn (VegetableRecommendationDTO $r) => [
                    'severity' => $r->severity->value,
                    'type' => $r->type,
                    'title' => $r->title,
                    'body' => $r->body,
                ],
                $this->recommendations,
            ),
            'expected_balance' => $this->expected_balance,
        ];
    }
}
