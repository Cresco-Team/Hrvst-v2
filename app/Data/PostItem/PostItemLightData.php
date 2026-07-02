<?php

namespace App\Data\PostItem;

use App\Enums\PostItemStatus;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class PostItemLightData extends Data
{
    public function __construct(
        public int $id,
        public int $vegetable_id,
        public ?string $variety_name,
        public ?string $vegetable_name,
        public ?string $vegetable_image_url,
        public float $quantity_kg,
        public ?PostItemStatus $status,
    ) {}
}
