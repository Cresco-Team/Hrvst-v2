<?php

namespace App\Data\Variety;

use App\Models\Product\Variety;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VarietyDetailData extends Data
{
    public function __construct(
        public int $id,
        public string $name,
        public int $vegetable_id,
        public string $vegetable_name,
        public int $category_id,
        public string $category_name,
        public int $supply_count,
        public int $demand_count,
        public array $supply_municipalities,
        public array $monthly_activity,
        public array $variety_calendar,
        public ?array $analytics,
    ) {}

    public static function fromModel(Variety $variety): self
    {
        $vegetable = $variety->vegetable;
        $category = $vegetable->category;

        return new self(
            id: $variety->id,
            name: $variety->name,
            vegetable_id: $vegetable->id,
            vegetable_name: $vegetable->name,
            category_id: $category->id,
            category_name: $category->name,
            supply_count: $variety->supply_count,
            demand_count: $variety->demand_count,
            supply_municipalities: $variety->supply_municipalities,
            monthly_activity: $variety->monthly_activity,
            variety_calendar: $variety->variety_calendar,
            analytics: $variety->analytics?->toArray(),
        );
    }
}