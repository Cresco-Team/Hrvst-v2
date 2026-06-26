<?php

namespace App\Data\Vegetable;

use App\Data\Category\CategoryData;
use App\Data\Concerns\ResolvesCounts;
use App\Models\Product\Vegetable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableSharedData extends Data
{
    use ResolvesCounts;

    public function __construct(
        public int $id,
        public string $name,
        public string $image_url,
        public int|Optional $varieties_count,
        public CategoryData|Lazy $category,
        /** @var DataCollection<int, VarietyCountData>|Lazy */
        public DataCollection|Lazy $varieties,
    ) {}

    public static function fromModel(Vegetable $vegetable): self
    {
        return new self(
            id: $vegetable->id,
            name: $vegetable->name,
            image_url: $vegetable->getFirstMediaUrl('vegetable_image'),
            varieties_count: self::resolveCount($vegetable, 'varieties_count'),
            category: Lazy::whenLoaded('category', $vegetable, fn () => CategoryData::fromModel($vegetable->category)),
            varieties: Lazy::whenLoaded('varieties', $vegetable, fn () => new DataCollection(
                VarietyCountData::class,
                $vegetable->varieties->map(fn ($v) => new VarietyCountData(
                    $v->id, $v->name, $v->supply_count, $v->demand_count
                )),
            )),
        );
    }
}