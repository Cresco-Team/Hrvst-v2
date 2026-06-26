<?php

namespace App\Data\Vegetable;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VarietyAdminRowData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $is_variety,
        public int $supply_count,
        public int $demand_count,
    ) {
        $this->is_variety = true;
    }
}
