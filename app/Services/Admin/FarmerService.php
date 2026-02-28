<?php

namespace App\Services\Admin;

use App\Models\Marketplace\FarmerSupply;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FarmerService
{
    public static function summary(): array
    {
        $newFarmerThisMonth = FarmerProfile::approved()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newSuppliesThisMonth = FarmerSupply::where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total_farmers' => FarmerProfile::approved()->count(),
            'new_farmers_this_month' => $newFarmerThisMonth,
            'total_supplies' => FarmerSupply::count(),
            'new_supplies_this_month' => $newSuppliesThisMonth,
        ];
    }

    public static function paginated(int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        return FarmerProfile::with([
            'user',
            'province',
            'municipality',
            'barangay',
            'supplies' => fn ($query) => $query
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['post.variety.vegetable.category'])
                ->orderBy('expiration_date', 'asc'),
        ])
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($farmer) {

                $ongoingSupplies = $farmer->supplies;

                return [
                    'id' => $farmer->id,
                    'user' => [
                        'id' => $farmer->user->id,
                        'name' => $farmer->user->name,
                        'email' => $farmer->user->email,
                        'phone_number' => $farmer->user->phone_number,
                        'image_url' => $farmer->user->image_url,
                    ],
                    'location' => [
                        'province' => $farmer->province->name,
                        'municipality' => $farmer->municipality->name,
                        'barangay' => $farmer->barangay->name,
                        'coordinates' => [
                            'lat' => $farmer->latitude,
                            'lng' => $farmer->longitude,
                        ],
                    ],
                    'farm_url' => $farmer->farm_url,
                    'ongoing_supplies_count' => $ongoingSupplies->count(),
                    'ongoing_supplies' => $ongoingSupplies->map(fn ($supply) => [
                        'id' => $supply->id,
                        'variety' => [
                            'id' => $supply->post->variety->id,
                            'name' => $supply->post->variety->vegetable->name.' '.$supply->post->variety->name,
                            'category' => $supply->post->variety->vegetable->category->name,
                            'image_url' => $supply->post->variety->image_url,
                        ],
                        'quantity_kg' => $supply->post->quantity_kg,
                        'expiration_date' => $supply->expiration_date->format('M d, Y'),
                    ]),
                    'joined_at' => $farmer->created_at->format('M d, Y'),
                    'joined_at_human' => $farmer->created_at->diffForHumans(),
                ];
            });
    }

    public static function find(int $farmerId): ?array
    {
        $farmer = FarmerProfile::with([
            'user',
            'province',
            'municipality',
            'barangay',
            'plantings' => fn ($query) => $query->with(['variety.vegetable.category'])
                ->orderBy('date_planted', 'desc'),
        ])
            ->where('is_approved', true)
            ->find($farmerId);

        if (! $farmer) {
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
                    'lat' => $farmer->latitude,
                    'lng' => $farmer->longitude,
                ],
            ],
            'farm_image' => $farmer->farm_image,
            'plantings' => $farmer->plantings->map(fn ($planting) => [
                'id' => $planting->id,
                'variety' => [
                    'id' => $planting->variety->id,
                    'name' => $planting->variety->vegetable->name.' '.$planting->variety->name,
                    'category' => $planting->variety->vegetable->category->name,
                    'image_path' => $planting->variety->image_path,
                ],
                'weight_kg' => $planting->weight_kg,
                'date_planted' => $planting->date_planted->format('M d, Y'),
                'expected_harvest_date' => $planting->expected_harvest_date->format('M d, Y'),
                'date_harvested' => $planting->date_harvested?->format('M d, Y'),
                'days_until_harvest' => $planting->days_unill_harvest,
                'status' => $planting->status,
            ]),
            'joined_at' => $farmer->created_at->format('M d, Y'),
            'joined_at_human' => $farmer->created_at->diffForHumans(),
        ];
    }

    public static function pending(): array
    {
        return FarmerProfile::with([
            'user',
            'province',
            'municipality',
            'barangay',
        ])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($farmer) => [
                'id'    => $farmer->id,
                'user'  => [
                    'id'            => $farmer->user->id,
                    'name'          => $farmer->user->name,
                    'email'         => $farmer->user->email,
                    'phone_number'  => $farmer->user->phone_number,
                    'image_path'    => $farmer->user->image_path,
                ],
                'location' => [
                    'province'      => $farmer->province->name,
                    'municipality'  => $farmer->municipality->name,
                    'barangay'      => $farmer->barangay->name,
                    'coordinates'   => [
                        'lat' => (float) $farmer->latitude,
                        'lng' => (float) $farmer->longitude,
                    ],
                ],
                'farm_url'              => $farmer->farm_url,
                'submitted_at'          => $farmer->created_at->format('M d, Y g:i A'),
                'submitted_at_human'    => $farmer->created_at->diffForHumans(),
            ])
            ->toArray();
    }
}
