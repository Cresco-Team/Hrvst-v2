<?php

namespace App\Data\Profile;

use App\Data\Concerns\ResolvesCounts;
use App\Data\PostItem\PostItemData;
use App\Data\PostItem\PostItemLightData;
use App\Models\Profiles\FarmerProfile;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class FarmerData extends Data
{
    use ResolvesCounts;

    public function __construct(
        public int $id,
        public string $joined_at,
        public string $joined_at_human,
        public UserData|Lazy $user,
        public string $full_address,
        public string|Lazy|null $barangay,
        public string|Lazy|null $municipality,
        public string|Lazy|null $province,
        public CoordinatesData $coordinates,
        public int|Optional $ongoing_supplies_count,
        /** @var PostItemLightData[|Lazy */
        public array|Lazy $supplies,
        /** @var PostItemData[]|Lazy */
        public array_diff_key|Lazy $supply_items,
        public UserInsightsData|Optional $insights,
        public bool $analytics_locked = false,
    ) {}

    public static function fromModel(FarmerProfile $farmer): self
    {
        return new self(
            id: $farmer->id,
            joined_at: $farmer->created_at->format('F j, Y'),
            joined_at_human: $farmer->created_at->diffForHumans(),
            user: Lazy::whenLoaded('user', $farmer, fn () => UserData::fromModel($farmer->user)),
            full_address: implode(', ', array_filter([
                $farmer->relationLoaded('barangay') ? $farmer->barangay?->name : null,
                $farmer->relationLoaded('municipality') ? $farmer->municipality?->name : null,
                $farmer->relationLoaded('province') ? $farmer->province?->name : null,
            ])),
            barangay: Lazy::whenLoaded('barangay', $farmer, fn () => $farmer->barangay?->name),
            municipality: Lazy::whenLoaded('municipality', $farmer, fn () => $farmer->municipality?->name),
            province: Lazy::whenLoaded('province', $farmer, fn () => $farmer->province?->name),
            coordinates: new CoordinatesData((float) $farmer->latitude, (float) $farmer->longitude),
            ongoing_supplies_count: self::resolveCount($farmer, 'ongoing_supplies_count'),
            supplies: Lazy::whenLoaded('posts', $farmer, fn () => PostItemLightData::collect(
                $farmer->posts->flatMap(
                    fn ($post) => $post->relationLoaded('postItems') ? $post->postItems : collect()
                ),
            )),
            supply_items: Lazy::whenLoaded('supplyItems', $farmer, fn () => PostItemData::collect(
                $farmer->supplyItems
            )),
            insights: isset($farmer->insights)
                ? $farmer->insights
                : Optional::create(),
            analytics_locked: $farmer->analytics_locked ?? false,
        );
    }
}
