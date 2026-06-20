<?php

namespace App\Data\Vegetable;

use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableLightData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $category,
        public ?string $image_url,
    ) {}
}
