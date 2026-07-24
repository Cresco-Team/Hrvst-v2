<?php

namespace App\DTOs\Product;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class VegetableForecastDTO
{
    public function __construct(
        public int $months_of_history,
        public string $forecast_confidence,
        /** @var array<int, array{month: string, label: string, supply_fulfilled_kg: float, supply_expired_kg: float, demand_fulfilled_kg: float, demand_expired_kg: float}> */
        public array $forecast = [],
    ) {}

    public function toArray(): array
    {
        return [
            'months_of_history' => $this->months_of_history,
            'forecast_confidence' => $this->forecast_confidence,
            'forecast' => $this->forecast,
        ];
    }
}
