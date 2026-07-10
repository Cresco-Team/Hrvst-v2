<?php

namespace App\DTOs\Product;

use App\Enums\Analytics\ImbalanceBand;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VarietyAnalyticsDTO
{
    public function __construct(
        public float $supply_demand_ratio,
        public ImbalanceBand $imbalance_band,
        public ?float $supply_fulfillment_rate,
        public ?float $demand_fulfillment_rate,
        public ?float $supply_volume_mom_pct,
        public ?float $demand_volume_mom_pct,
        /** @var VarietyRecommendationDTO[] */
        public array $recommendations,
        public int $months_of_history,
        public string $forecast_confidence,
        /** @var array<int, array{...}> */
        public array $forecast = [],
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
                fn (VarietyRecommendationDTO $r) => [
                    'severity' => $r->severity->value,
                    'type' => $r->type,
                    'title' => $r->title,
                    'body' => $r->body,
                ],
                $this->recommendations,
            ),
            'months_of_history' => $this->months_of_history,
            'forecast_confidence' => $this->forecast_confidence,
            'forecast' => $this->forecast,
        ];
    }
}
