<?php

namespace App\Data\Variety;

use App\Data\Concerns\ResolvesCounts;
use App\Data\Vegetable\VegetableData;
use App\Models\Product\Variety;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VarietyData extends Data
{
    use ResolvesCounts;

    public function __construct(
        public int $id,
        public string $name,
        public VegetableData|Lazy $vegetable,
        public int|Optional $supply_count,
        public int|Optional $demand_count,
    ) {}

    public static function fromModel(Variety $variety): self
    {
        return new self(
            id: $variety->id,
            name: $variety->name,
            vegetable: Lazy::whenLoaded('vegetable', $variety, fn () => VegetableData::fromModel($variety->vegetable)),
            supply_count: self::resolveCount($variety, 'supply_count'),
            demand_count: self::resolveCount($variety, 'demand_count'),
        );
    }
}
