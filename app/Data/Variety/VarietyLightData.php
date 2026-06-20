<?php

namespace App\Data\Variety;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VarietyLightData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
