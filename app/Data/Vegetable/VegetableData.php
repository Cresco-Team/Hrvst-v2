<?php

namespace App\Data\Vegetable;

use App\Data\Category\CategoryData;
use App\Data\Concerns\ResolvesCounts;
use App\Data\Variety\VarietyData;
use App\Models\Product\Vegetable;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableData extends Data
{
    use ResolvesCounts;

    public function __construct(
        public int $id,
        public string $name,
        public string $image_url,
        public int|Optional $varieties_count,
        public CategoryData|Lazy $category,
        /** @var DataCollection<int, VarietyData>|Lazy */
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
            varieties: Lazy::whenLoaded('varieties', $vegetable, fn () => VarietyData::collect(
                $vegetable->varieties, DataCollection::class
            )),
        );
    }
}