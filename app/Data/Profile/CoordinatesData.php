<?php

namespace App\Data\Profile;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class CoordinatesData extends Data
{
    public function __construct(
        public float $lat,
        public float $lng,
    ) {}
}