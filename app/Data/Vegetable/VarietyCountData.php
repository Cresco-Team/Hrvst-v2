<?php

namespace App\Data\Vegetable;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VarietyCountData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $supply_count,
        public int $demand_count,
    ) {}
}
