<?php

namespace App\Data\Vegetable;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableWasteData extends Data
{
    public function __construct(
        public int $id,
        public string $display_name,
        public string $image_url,
        public float $wasted_kg,
    ) {}
}
