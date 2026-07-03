<?php

namespace App\Data\Vegetable;

use App\Data\Category\CategoryData;
use App\Data\Concerns\ResolvesCounts;
use App\Models\Product\Vegetable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableSharedData extends Data
{
    use ResolvesCounts;

    public function __construct(
        public int $id,
        public string $vegetable_name,
        public ?string $variety_name,
        public ?string $local_name,
        public string $display_name,
        public string $image_url,
        public CategoryData|Lazy $category,
        public int|Optional $supply_count,
        public int|Optional $demand_count,
    ) {}

    public static function fromModel(Vegetable $vegetable): self
    {
        return new self(
            id: $vegetable->id,
            vegetable_name: $vegetable->vegetable_name,
            variety_name: $vegetable->variety_name,
            local_name: $vegetable->local_name,
            display_name: $vegetable->display_name,
            image_url: $vegetable->image_url,
            category: Lazy::whenLoaded('category', $vegetable, fn () => CategoryData::fromModel($vegetable->category)),
            supply_count: self::resolveCount($vegetable, 'supply_count'),
            demand_count: self::resolveCount($vegetable, 'demand_count'),
        );
    }
}
