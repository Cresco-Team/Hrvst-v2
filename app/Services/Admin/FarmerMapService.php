<?php

namespace App\Services\Admin;

use App\Models\Address\Municipality;
use App\Models\Product\Vegetable;
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
        return Vegetable::query()
            ->whereHas('postItems', fn (Builder $q) => $q
                ->ongoing()
                ->whereHas('post', fn (Builder $q) => $q->supply())
            )
            ->with('category')
            ->orderBy('vegetable_name')
            ->get()
            ->groupBy('category.name')
            ->map(fn ($rows) => $rows->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->variety_name ? "{$v->vegetable_name} {$v->variety_name}" : $v->vegetable_name,
                'category' => $v->category->name,
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
                'province',
                'municipality',
                'barangay',
                'posts' => fn ($q) => $q
                    ->supply()
                    ->with(['postItems' => fn ($q) => $q->ongoing()->with('vegetable')]),
            ]);

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        if ($varietyId) {
            $query->whereHas('posts', fn (Builder $q) => $q
                ->supply()
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
                    'province_id' => $farmer->province_id,
                    'province' => $farmer->province?->name,
                    'municipality_id' => $farmer->municipality_id,
                    'municipality' => $farmer->municipality->name,
                    'barangay_id' => $farmer->barangay_id,
                    'barangay' => $farmer->barangay?->name,
                    'ongoing_supplies_count' => $ongoingItems->count(),
                    'supplies_summary' => $ongoingItems
                        ->groupBy(fn ($item) => $item->vegetable->vegetable_name)
                        ->map(fn ($items, string $vegetableName) => [
                            'vegetable' => $vegetableName,
                            'count' => $items->count(),
                            'varieties' => $items->pluck('vegetable.variety_name')->filter()->unique()->values()->toArray(),
                        ])
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }
}
