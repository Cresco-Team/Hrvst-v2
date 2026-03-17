<?php

namespace App\Services\Admin;

use App\Models\Address\Municipality;
use App\Models\Marketplace\Post;
use App\Models\Profiles\FarmerProfile;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;

class FarmerMapService
{
    public function getMunicipalityOptions(): array
    {
        return Municipality::query()
            ->whereHas('farmers', fn ($q) => $q->where('is_approved', true))
            ->with('province')
            ->orderBy('name')
            ->get()
            ->map(fn ($municipality) => [
                'id'       => $municipality->id,
                'name'     => $municipality->name,
                'province' => $municipality->province->name,
                'label'    => "{$municipality->name}, {$municipality->province->name}",
            ])
            ->toArray();
    }

    public function getSupplyOptions(): array
    {
        return Variety::query()
            ->whereHas('posts', fn ($q) => $q->ongoing()->supply())
            ->with('vegetable.category')
            ->orderBy('name')
            ->get()
            ->groupBy('vegetable.category.name')
            ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                'id'       => $variety->id,
                'name'     => "{$variety->vegetable->name} {$variety->name}",
                'category' => $variety->vegetable->category->name,
            ])->values()->toArray())
            ->toArray();
    }

    public function getFarmersForMap(?int $municipalityId = null, ?int $varietyId = null, ?array $bounds = null): array
    {
        $query = FarmerProfile::query()
            ->with([
                'user',
                'municipality',
                'supplies' => fn ($q) => $q->ongoing()->with('variety.vegetable'),
            ])
            ->where('is_approved', true);

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        if ($varietyId) {
            $query->whereHas('supplies', fn (Builder $q) =>
                $q->ongoing()->where('variety_id', $varietyId)
            );
        }

        if ($bounds) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        return $query->get()
            ->map(function (FarmerProfile $farmer) {
                $ongoingSupplies = $farmer->supplies;

                return [
                    'id'          => $farmer->id,
                    'coordinates' => [
                        'lat' => (float) $farmer->latitude,
                        'lng' => (float) $farmer->longitude,
                    ],
                    'farmer_name'            => $farmer->user->name,
                    'municipality'           => $farmer->municipality->name,
                    'ongoing_supplies_count' => $ongoingSupplies->count(),
                    'supplies_summary'       => $ongoingSupplies
                        ->groupBy(fn (Post $supply) => $supply->variety->vegetable->name)
                        ->map(fn ($supplies, string $vegetableName) => [
                            'vegetable' => $vegetableName,
                            'count'     => $supplies->count(),
                            'varieties' => $supplies->pluck('variety.name')->unique()->values()->toArray(),
                        ])
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }
}
