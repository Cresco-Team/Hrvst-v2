<?php

namespace App\Services\Admin;

use App\Models\Address\Municipality;
use App\Models\Marketplace\FarmerSupply;
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
            ->whereHas('posts', function ($q) {
                $q->ongoing()->whereHasMorph('postable', FarmerSupply::class);
            })
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
                'supplies' => fn ($q) => $q
                    ->whereHas('post', fn ($q) => $q->ongoing())
                    ->with('post.variety.vegetable'),
            ])
            ->where('is_approved', true);

        if ($municipalityId) {
            $query->where('municipality_id', $municipalityId);
        }

        if ($varietyId) {
            $query->whereHas('supplies.post', function (Builder $q) use ($varietyId) {
                $q->ongoing()->where('variety_id', $varietyId);
            });
        }

        if ($bounds) {
            $query->whereBetween('latitude', [$bounds['south'], $bounds['north']])
                ->whereBetween('longitude', [$bounds['west'], $bounds['east']]);
        }

        return $query->get()
            ->map(function ($farmer) {
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
                        ->groupBy(fn ($supply) => $supply->post->variety->vegetable->name)
                        ->map(fn ($supplies, $vegetableName) => [
                            'vegetable' => $vegetableName,
                            'count'     => $supplies->count(),
                            'varieties' => $supplies->pluck('post.variety.name')->unique()->values()->toArray(),
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
                'user.media',
                'media',
                'province',
                'municipality',
                'barangay',
                'supplies' => fn ($q) => $q
                    ->whereHas('post', fn ($q) => $q->ongoing())
                    ->with(['media', 'post.variety.media', 'post.variety.vegetable.category'])
                    ->orderBy('expiration_date', 'asc'),
            ])
            ->find($farmerId);

        if (! $farmer) {
            return null;
        }

        return [
            'id'   => $farmer->id,
            'user' => [
                'id'           => $farmer->user->id,
                'name'         => $farmer->user->name,
                'email'        => $farmer->user->email,
                'phone_number' => $farmer->user->phone_number,
                'avatar_url'   => $farmer->user->getFirstMediaUrl('avatar'),
            ],
            'location' => [
                'province'     => $farmer->province->name,
                'municipality' => $farmer->municipality->name,
                'barangay'     => $farmer->barangay->name,
                'full_address' => "{$farmer->barangay->name}, {$farmer->municipality->name}, {$farmer->province->name}",
                'coordinates'  => [
                    'lat' => (float) $farmer->latitude,
                    'lng' => (float) $farmer->longitude,
                ],
            ],
            'farm_url'         => $farmer->getFirstMediaUrl('farm_photo'),
            'ongoing_supplies' => $farmer->supplies->map(fn ($supply) => [
                'id'      => $supply->id,
                'variety' => [
                    'id'        => $supply->post->variety->id,
                    'name'      => $supply->post->variety->vegetable->name.' '.$supply->post->variety->name,
                    'category'  => $supply->post->variety->vegetable->category->name,
                    'image_url' => $supply->post->variety->getFirstMediaUrl('variety_image'),
                ],
                'quantity_kg'          => $supply->post->quantity_kg,
                'created_at'           => $supply->created_at->format('M d, Y'),
                'expiration_date'      => $supply->expiration_date->format('M d, Y'),
                'days_until_expiration' => $supply->days_until_expiration, // was: days_unill_expiration (typo)
            ])->toArray(),
            'statistics' => [
                'total_ongoing_supplies' => $farmer->supplies->count(),
                'total_quantity'         => $farmer->supplies->sum(fn ($s) => $s->post->quantity_kg),
            ],
            'joined_at'       => $farmer->created_at->format('M d, Y'),
            'joined_at_human' => $farmer->created_at->diffForHumans(),
        ];
    }
}
