<?php

namespace App\DTOs\Vegetable;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
readonly class ExpectedBalanceComputation
{
    public function __construct(
        public string $source_label,
        public float $supply_kg,
        public float $demand_kg,
        public ?float $diff_pct,
    ) {}

    public function toArray(): array
    {
        return [
            'source_label' => $this->source_label,
            'supply_kg' => $this->supply_kg,
            'demand_kg' => $this->demand_kg,
            'diff_pct' => $this->diff_pct,
        ];
    }
}
