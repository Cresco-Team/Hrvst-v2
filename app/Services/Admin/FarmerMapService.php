<?php

namespace App\Services\Admin;

use App\Models\Address\Municipality;
use App\Models\Product\Variety;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Database\Eloquent\Builder;

class FarmerMapService
{
    public function getMunicipalityOptions(): array
    {
        return Municipality::query()
            ->with('province')
            ->orderBy('name')
            ->get()
            ->map(fn ($municipality) => [
                'id' => $municipality->id,
                'name' => $municipality->name,
                'province' => $municipality->province->name,
                'label' => "{$municipality->name}, {$municipality->province->name}",
            ])
            ->toArray();
    }

    public function getSupplyOptions(): array
    {
        // Varieties that have ongoing PostItems from harvested supply posts
        return Variety::query()
            ->whereHas('postItems', fn (Builder $q) => $q
                ->ongoing()
                ->whereHas('post', fn (Builder $q) => $q->supply()->harvested())
            )
            ->with('vegetable.category')
            ->orderBy('name')
            ->get()
            ->groupBy('vegetable.category.name')
            ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                'id' => $variety->id,
                'name' => "{$variety->vegetable->name} {$variety->name}",
                'category' => $variety->vegetable->category->name,
            ])->values()->toArray())
            ->toArray();
    }

    public function getFarmersForMap(
        ?int $municipalityId = null,
        ?int $varietyId = null,
        ?array $bounds = null,
    ): array {
        $query = FarmerProfile::query()
            ->with([
                'user',
                'municipality',
                // Load harvested supply posts with their ongoing PostItems
                'posts' => fn ($q) => $q
                    ->supply()
                    ->harvested()
                    ->with([
                        'postItems' => fn ($q) => $q
                            ->ongoing()
                            ->with('variety.vegetable'),
                    ]),
            ]);

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        if ($varietyId) {
            $query->whereHas('posts', fn (Builder $q) => $q
                ->supply()
                ->harvested()
                ->whereHas('postItems', fn (Builder $q) => $q
                    ->ongoing()
                    ->where('variety_id', $varietyId)
                )
            );
        }

        if ($bounds) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        return $query->get()
            ->map(function (FarmerProfile $farmer) {
                $ongoingItems = $farmer->posts->flatMap(
                    fn ($post) => $post->relationLoaded('postItems') ? $post->postItems : collect()
                );

                return [
                    'id' => $farmer->id,
                    'coordinates' => [
                        'lat' => (float) $farmer->latitude,
                        'lng' => (float) $farmer->longitude,
                    ],
                    'farmer_name' => $farmer->user->name,
                    'municipality' => $farmer->municipality->name,
                    'ongoing_supplies_count' => $ongoingItems->count(),
                    'supplies_summary' => $ongoingItems
                        ->groupBy(fn ($item) => $item->variety->vegetable->name)
                        ->map(fn ($items, string $vegetableName) => [
                            'vegetable' => $vegetableName,
                            'count' => $items->count(),
                            'varieties' => $items->pluck('variety.name')->unique()->values()->toArray(),
                        ])
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }
}
