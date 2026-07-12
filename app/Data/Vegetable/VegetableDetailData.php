<?php

namespace App\Data\Vegetable;

use App\Models\Product\Vegetable;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class VegetableDetailData extends Data
{
    public function __construct(
        public int $id,
        public int $vegetable_id,
        public string $vegetable_name,
        public ?string $variety_name,
        public string $display_name,
        public int $category_id,
        public int $supply_count,
        public int $demand_count,
        public array $supply_municipalities,
        public array $monthly_activity,
        public array $vegetable_calendar,
        public ?array $analytics,
        public bool $analytics_locked,
        public ?string $upgrade_feature,
    ) {}

    public static function fromModel(Vegetable $vegetable): self
    {
        $category = $vegetable->category;

        return new self(
            id: $vegetable->id,
            vegetable_id: $vegetable->id,
            vegetable_name: $vegetable->vegetable_name,
            variety_name: $vegetable->variety_name,
            display_name: $vegetable->display_name,
            category_id: $category->id,
            supply_count: $vegetable->supply_count,
            demand_count: $vegetable->demand_count,
            supply_municipalities: $vegetable->supply_municipalities,
            monthly_activity: $vegetable->monthly_activity,
            vegetable_calendar: $vegetable->vegetable_calendar,
            analytics: $vegetable->analytics?->toArray(),
            analytics_locked: $vegetable->analytics_locked ?? false,
            upgrade_feature: $vegetable->upgrade_feature ?? null,
        );
    }
}
