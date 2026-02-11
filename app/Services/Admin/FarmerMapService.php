<?php

namespace App\Services\Admin;

use App\Models\Address\Municipality;
use App\Models\Profiles\FarmerProfile;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FarmerMapService
{
    /**
     * Get municipality filter options
     */
    public function getMunicipalityOptions(): array
    {
        return Municipality::query()
            ->whereHas('farmers', fn($q) => $q->where('is_approved', true))
            ->with('province')
            ->orderBy('name')
            ->get()
            ->map(fn($municipality) => [
                'id' => $municipality->id,
                'name' => $municipality->name,
                'province' => $municipality->province->name,
                'label' => "{$municipality->name}, {$municipality->province->name}",
            ])
            ->toArray();
    }

    /**
     * Get planting (variety) filter options
     */
    public function getPlantingOptions(): array
    {
        return Variety::query()
            ->whereHas('plantings', function ($q) {
                $q->where('status', 'active')
                    ->whereHas('farmer', fn($fq) => $fq->where('is_approved', true));
            })
            ->with('vegetable.category')
            ->orderBy('name')
            ->get()
            ->groupBy('vegetable.category.name')
            ->map(fn($varieties) => $varieties->map(fn($variety) => [
                'id' => $variety->id,
                'name' => "{$variety->vegetable->name} {$variety->name}",
                'category' => $variety->vegetable->category->name,
            ])->values()->toArray())
            ->toArray();
    }

    /**
     * Get farmers for map markers with filtering
     */
    public function getFarmersForMap(
        ?int $municipalityId = null,
        ?int $varietyId = null,
        ?array $bounds = null
    ): array {
        $query = FarmerProfile::query()
            ->with([
                'user',
                'municipality',
                'plantings' => fn($q) => $q->where('status', 'active')
                    ->with('variety.vegetable'),
            ])
            ->where('is_approved', true);

        // Filter by municipality
        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        // Filter by variety/planting
        if ($varietyId) {
            $query->whereHas('plantings', function (Builder $q) use ($varietyId) {
                $q->where('status', 'active')
                    ->where('variety_id', $varietyId);
            });
        }

        // Filter by map bounds (for performance with large datasets)
        if ($bounds) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        return $query->get()
            ->map(function ($farmer) {
                $activePlantings = $farmer->plantings;
                
                return [
                    'id' => $farmer->id,
                    'coordinates' => [
                        'lat' => (float) $farmer->latitude,
                        'lng' => (float) $farmer->longitude,
                    ],
                    'farmer_name' => $farmer->user->name,
                    'municipality' => $farmer->municipality->name,
                    'active_plantings_count' => $activePlantings->count(),
                    'plantings_summary' => $activePlantings
                        ->groupBy('variety.vegetable.name')
                        ->map(fn($group) => [
                            'vegetable' => $group->first()->variety->vegetable->name,
                            'count' => $group->count(),
                            'varieties' => $group->pluck('variety.name')->unique()->values()->toArray(),
                        ])
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Get detailed farmer information for sidebar
     */
    public function getFarmerDetails(int $farmerId): ?array
    {
        $farmer = FarmerProfile::query()
            ->where('is_approved', true)
            ->with([
                'user',
                'province',
                'municipality',
                'barangay',
                'plantings' => fn($q) => $q->where('status', 'active')
                    ->with(['variety.vegetable.category'])
                    ->orderBy('expected_harvest_date', 'asc'),
            ])
            
            ->find($farmerId);

        if (!$farmer) {
            return null;
        }

        return [
            'id' => $farmer->id,
            'user' => [
                'id' => $farmer->user->id,
                'name' => $farmer->user->name,
                'email' => $farmer->user->email,
                'phone_number' => $farmer->user->phone_number,
                'user_image' => $farmer->user->user_image,
            ],
            'location' => [
                'province' => $farmer->province->name,
                'municipality' => $farmer->municipality->name,
                'barangay' => $farmer->barangay->name,
                'full_address' => "{$farmer->barangay->name}, {$farmer->municipality->name}, {$farmer->province->name}",
                'coordinates' => [
                    'lat' => (float) $farmer->latitude,
                    'lng' => (float) $farmer->longitude,
                ],
            ],
            'farm_image' => $farmer->farm_image,
            'active_plantings' => $farmer->plantings->map(fn($planting) => [
                'id' => $planting->id,
                'variety' => [
                    'id' => $planting->variety->id,
                    'name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                    'category' => $planting->variety->vegetable->category->name,
                    'image_path' => $planting->variety->image_path,
                ],
                'weight_kg' => $planting->weight_kg,
                'date_planted' => $planting->date_planted->format('M d, Y'),
                'expected_harvest_date' => $planting->expected_harvest_date->format('M d, Y'),
                'days_until_harvest' => $planting->days_unill_harvest,
                'status_badge' => $planting->status_badge,
            ])->toArray(),
            'statistics' => [
                'total_active_plantings' => $farmer->plantings->count(),
                'total_weight' => $farmer->plantings->sum('weight_kg'),
                'harvesting_soon' => $farmer->plantings->filter(function ($planting) {
                    return $planting->days_unill_harvest !== null 
                        && $planting->days_unill_harvest >= 0 
                        && $planting->days_unill_harvest <= 7;
                })->count(),
            ],
            'joined_at' => $farmer->created_at->format('M d, Y'),
            'joined_at_human' => $farmer->created_at->diffForHumans(),
        ];
    }
}
