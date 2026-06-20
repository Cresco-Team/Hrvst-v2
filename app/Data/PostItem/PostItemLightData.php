<?php

namespace App\Data\PostItem;

use App\Data\Variety\VarietyLightData;
use App\Enums\PostItemStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostItemLightData extends Data
{
    public function __construct(
        public int $id,
        public int $variety_id,
        public ?VarietyLightData $variety,
        public float $quantity_kg,
        public PostItemStatus $status,
    ) {}
}
