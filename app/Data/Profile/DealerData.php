<?php

namespace App\Data\Profile;

use App\Data\Concerns\ResolvesCounts;
use App\Data\PostItem\PostItemData;
use App\Data\PostItem\PostItemLightData;
use App\Models\Profiles\DealerProfile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class DealerData extends Data
{
    use ResolvesCounts;

    public function __construct(
        public int $id,
        public string $joined_at,
        public string $joined_at_human,
        public UserData|Lazy $user,
        public int|Optional $ongoing_demands_count,
        /** @var PostItemLightData[]|Lazy */
        public array|Lazy $demands,
        /** @var PostItemData[]|Lazy */
        public array_diff_key|Lazy $demand_items,
        public UserInsightsData|Optional $insights,
        public bool $analytics_locked = false,
    ) {}

    public static function fromModel(DealerProfile $dealer): self
    {
        return new self(
            id: $dealer->id,
            joined_at: $dealer->created_at->format('F j, Y'),
            joined_at_human: $dealer->created_at->diffForHumans(),
            user: Lazy::whenLoaded('user', $dealer, fn () => UserData::fromModel($dealer->user)),
            ongoing_demands_count: self::resolveCount($dealer, 'ongoing_demands_count'),
            demands: Lazy::whenLoaded('posts', $dealer, fn () => PostItemLightData::collect(
                $dealer->posts->flatMap(
                    fn ($post) => $post->relationLoaded('postItems') ? $post->postItems : collect()
                ),
            )),
            demand_items: Lazy::whenLoaded('demandItems', $dealer, fn () => PostItemData::collect(
                $dealer->demandItems
            )),
            insights: isset($dealer->insights)
                ? $dealer->insights
                : Optional::create(),
                analytics_locked: $dealer->analytics_locked ?? false,
        );
    }
}
