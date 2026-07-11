<?php

namespace App\Data\Profile;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class MonthlyVolumeData extends Data
{
    public function __construct(
        public string $month,
        public string $label,
        public float $total_kg,
    ) {}
}