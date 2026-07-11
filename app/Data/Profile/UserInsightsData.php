<?php

namespace App\Data\Profile;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UserInsightsData extends Data
{
    public function __construct(
        public ?float $fulfillment_rate,
        public int $total_posts,
        public float $posts_per_month,
        public ?string $last_active,
        public ?string $last_active_human,
        /** @var TopVegetableData[] */
        #[DataCollectionOf(TopVegetableData::class)]
        public array $top_varieties,
        /** @var MonthlyVolumeData[] */
        #[DataCollectionOf(MonthlyVolumeData::class)]
        public array $monthly_volume,
    ) {}
}
