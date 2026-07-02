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
        /**
         * 6-month forward forecast derived from 3-year seasonal history.
         *
         * @var array<int, array{month: string, label: string, supply_kg: float, demand_kg: float}>
         */
        public array $forecast = [],
    ) {}

    public function toArray(): array
    {
        return [
            'supply_demand_ratio'     => $this->supply_demand_ratio,
            'imbalance_band'          => $this->imbalance_band->value,
            'supply_fulfillment_rate' => $this->supply_fulfillment_rate,
            'demand_fulfillment_rate' => $this->demand_fulfillment_rate,
            'supply_volume_mom_pct'   => $this->supply_volume_mom_pct,
            'demand_volume_mom_pct'   => $this->demand_volume_mom_pct,
            'recommendations'         => array_map(
                fn (VarietyRecommendationDTO $r) => [
                    'severity' => $r->severity->value,
                    'type'     => $r->type,
                    'title'    => $r->title,
                    'body'     => $r->body,
                ],
                $this->recommendations,
            ),
            'forecast' => $this->forecast,
        ];
    }
}
