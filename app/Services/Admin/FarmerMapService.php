<?php

namespace App\Services\Admin;

use App\FarmerOfferingStatus;
use App\Models\Address\Municipality;
use App\Models\Profiles\FarmerProfile;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;

class FarmerMapService
{
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

    public function getOfferingOptions(): array
    {
        return Variety::query()
            ->whereHas('offerings', function ($q) {
                $q->where('status', FarmerOfferingStatus::Available)
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

    public function getFarmersForMap(
        ?int $municipalityId = null,
        ?int $varietyId = null,
        ?array $bounds = null
    ): array {
        $query = FarmerProfile::query()
            ->with([
                'user',
                'municipality',
                'offerings' => fn($q) => $q->where('status', FarmerOfferingStatus::Available)
                    ->with('variety.vegetable'),
            ])
            ->where('is_approved', true);

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        if ($varietyId) {
            $query->whereHas('offerings', function (Builder $q) use ($varietyId) {
                $q->where('status', FarmerOfferingStatus::Available)
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
                $availableOfferings = $farmer->offerings;
                
                return [
                    'id' => $farmer->id,
                    'coordinates' => [
                        'lat' => (float) $farmer->latitude,
                        'lng' => (float) $farmer->longitude,
                    ],
                    'farmer_name' => $farmer->user->name,
                    'municipality' => $farmer->municipality->name,
                    'available_offerings_count' => $availableOfferings->count(),
                    'offerings_summary' => $availableOfferings
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

    public function getFarmerDetails(int $farmerId): ?array
    {
        $farmer = FarmerProfile::query()
            ->where('is_approved', true)
            ->with([
                'user',
                'province',
                'municipality',
                'barangay',
                'offerings' => fn($q) => $q->where('status', FarmerOfferingStatus::Available)
                    ->with(['variety.vegetable.category'])
                    ->orderBy('expiration_date', 'asc'),
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
            'available_offerings' => $farmer->offerings->map(fn($offering) => [
                'id' => $offering->id,
                'variety' => [
                    'id' => $offering->variety->id,
                    'name' => $offering->variety->vegetable->name . ' ' . $offering->variety->name,
                    'category' => $offering->variety->vegetable->category->name,
                    'image_path' => $offering->variety->image_path,
                ],
                'weight_kg' => $offering->weight_kg,
                'created_at' => $offering->created_at->format('M d, Y'),
                'expiration_date' => $offering->expiration_date->format('M d, Y'),
                'days_until_expiration' => $offering->days_unill_expiration,
            ])->toArray(),
            'statistics' => [
                'total_available_offerings' => $farmer->offerings->count(),
                'total_weight' => $farmer->offerings->sum('weight_kg'),
            ],
            'joined_at' => $farmer->created_at->format('M d, Y'),
            'joined_at_human' => $farmer->created_at->diffForHumans(),
        ];
    }
}
