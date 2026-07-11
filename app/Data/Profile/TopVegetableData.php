<?php

namespace App\Data\Profile;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class TopVegetableData extends Data
{
    public function __construct(
        public int $vegetable_id,
        public string $display_name,
        public string $image_url,
        public int $post_count,
        public float $value_kg,
    ) {}
}
