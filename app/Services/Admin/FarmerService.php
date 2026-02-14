<?php

namespace App\Services\Admin;

use App\FarmerOfferingStatus;
use App\Models\Marketplace\FarmerOffering;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FarmerService
{
    /**
     * Get paginated list of approved farmers with their active plantings
     */
    public static function paginated(int $perPage = 20, ?int $page = null): LengthAwarePaginator
{
    return FarmerProfile::with([
        'user',
        'province',
        'municipality',
        'barangay',
        'offerings' => fn($query) => $query->where('status', FarmerOfferingStatus::Available)
            ->with(['variety.vegetable.category'])
            ->orderBy('expiration_date', 'asc'),
    ])
        ->where('is_approved', true)
        ->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page)
        ->through(function ($farmer) {
            return [
                'id' => $farmer->id,
                'user' => [
                    'id' => $farmer->user->id,
                    'name' => $farmer->user->name,
                    'email' => $farmer->user->email,
                    'phone_number' => $farmer->user->phone_number,
                    'image_path' => $farmer->user->image_path,
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
                'farm_image' => $farmer->farm_image,
                'available_offerings_count' => $farmer->offerings->count(),
                'available_offerings' => $farmer->offerings->map(fn($offering) => [
                    'id' => $offering->id,
                    'variety' => [
                        'id' => $offering->variety->id,
                        'name' => $offering->variety->vegetable->name . ' ' . $offering->variety->name,
                        'category' => $offering->variety->vegetable->category->name,
                        'image_path' => $offering->variety->image_path,
                    ],
                    'weight_kg' => $offering->weight_kg,
                    'expiration_date' => $offering->expiration_date->format('M d, Y'),
                    'status_badge' => $offering->status_badge,
                ]),
                'joined_at' => $farmer->created_at->format('M d, Y'),
                'joined_at_human' => $farmer->created_at->diffForHumans(),
            ];
        });
}

    /**
     * Get summary statistics for farmers
     */
    public static function summary(): array
    {
        $totalFarmers = FarmerProfile::where('is_approved', true)
            ->count();
        $newFarmerThisMonth = FarmerProfile::where('is_approved', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newOfferingsThisMonth = FarmerOffering::where('created_at', '>=', now()->startOfMonth())
            ->count();
        
        return [
            'total_farmers' => $totalFarmers,
            'new_farmers_this_month' => $newFarmerThisMonth,
            'total_offerings' => FarmerOffering::count(),
            'new_offerings_this_month' => $newOfferingsThisMonth,
        ];
    }

    /**
     * Get detailed farmer information
     */
    public static function find(int $farmerId): ?array
    {
        $farmer = FarmerProfile::with([
            'user',
            'province',
            'municipality',
            'barangay',
            'plantings' => fn($query) => $query->with(['variety.vegetable.category'])
                ->orderBy('date_planted', 'desc'),
        ])
            ->where('is_approved', true)
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
                    'lat' => $farmer->latitude,
                    'lng' => $farmer->longitude,
                ],
            ],
            'farm_image' => $farmer->farm_image,
            'plantings' => $farmer->plantings->map(fn($planting) => [
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
            ->where('is_approved', false)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($farmer) => [
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
                'submitted_at' => $farmer->created_at->format('M d, Y g:i A'),
                'submitted_at_human' => $farmer->created_at->diffForHumans(),
            ])
            ->toArray();
    }

    public static function approve(int $farmerId): bool
    {
        $farmer = FarmerProfile::where('is_approved', false)->find($farmerId);

        if (!$farmer) {
            return false;
        }

        return $farmer->update(['is_approved' => true]);
    }

    public static function reject(int $farmerId): bool
    {
        $farmer = FarmerProfile::where('is_approved', false)->find($farmerId);

        if (!$farmer) {
            return false;
        }

        $user = $farmer->user;
        $farmer->delete();
        $user->delete();

        return true;
    }
}
