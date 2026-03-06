<?php

namespace App\Services\Admin;

use App\Enums\PostStatus;
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
            'total_farmers'           => FarmerProfile::approved()->count(),
            'new_farmers_this_month'  => $newFarmerThisMonth,
            'total_supplies'          => FarmerSupply::count(),
            'new_supplies_this_month' => $newSuppliesThisMonth,
        ];
    }

    public static function paginated(int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        return FarmerProfile::with([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
            'supplies' => fn ($query) => $query
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['media', 'post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('expiration_date', 'asc'),
        ])
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($farmer) {
                $ongoingSupplies = $farmer->supplies->filter(
                    fn ($supply) => $supply->post->status === PostStatus::Ongoing
                );

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
                        'coordinates'  => [
                            'lat' => $farmer->latitude,
                            'lng' => $farmer->longitude,
                        ],
                    ],
                    'farm_url'               => $farmer->getFirstMediaUrl('farm_photo'),
                    'ongoing_supplies_count' => $ongoingSupplies->count(),
                    'ongoing_supplies'       => $ongoingSupplies->map(fn ($supply) => [
                        'id'      => $supply->id,
                        'variety' => [
                            'id'        => $supply->post->variety->id,
                            'name'      => $supply->post->variety->vegetable->name.' '.$supply->post->variety->name,
                            'category'  => $supply->post->variety->vegetable->category->name,
                            'image_url' => $supply->post->variety->getFirstMediaUrl('variety_image'),
                        ],
                        'quantity_kg'     => $supply->post->quantity_kg,
                        'expiration_date' => $supply->expiration_date->format('M d, Y'),
                    ]),
                    'joined_at'       => $farmer->created_at->format('M d, Y'),
                    'joined_at_human' => $farmer->created_at->diffForHumans(),
                ];
            });
    }

    public static function show(int $farmerId): ?array
    {
        $farmer = FarmerProfile::with([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
            'supplies' => fn ($query) => $query
                ->whereHas('post')
                ->with(['media', 'post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('expiration_date', 'desc'),
        ])
            ->where('is_approved', true)
            ->find($farmerId);

        if (! $farmer) {
            return null;
        }

        $formatSupply = fn ($supply) => [
            'id'      => $supply->id,
            'variety' => [
                'id'        => $supply->post->variety->id,
                'name'      => $supply->post->variety->vegetable->name.' '.$supply->post->variety->name,
                'category'  => $supply->post->variety->vegetable->category->name,
                'image_url' => $supply->post->variety->getFirstMediaUrl('variety_image'),
            ],
            'title'            => $supply->post->title,
            'image_url'        => $supply->getFirstMediaUrl('supply_image'),
            'quantity_kg'      => (float) $supply->post->quantity_kg,     // was: $supply->quantity_kg (bug)
            'offered_price'    => (float) $supply->post->offered_price,
            'price_flag'       => $supply->post->price_flag,
            'expiration_date'  => $supply->expiration_date->format('M d, Y'),
            'status'           => $supply->post->status,
            'created_at'       => $supply->created_at->format('M d, Y'),
            'created_at_human' => $supply->created_at->diffForHumans(),   // was: 'created_at_haman' (typo)
        ];

        $ongoingSupplies   = $farmer->supplies->filter(fn ($s) => $s->post->status === PostStatus::Ongoing)->values();
        $archivedSupplies  = $farmer->supplies->filter(fn ($s) => $s->post->status === PostStatus::Archived)->values();
        $fulfilledSupplies = $farmer->supplies->filter(fn ($s) => $s->post->status === PostStatus::Fulfilled)->values();

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
                    'lat' => $farmer->latitude,
                    'lng' => $farmer->longitude,
                ],
            ],
            'farm_url' => $farmer->getFirstMediaUrl('farm_photo'),
            'supplies' => [
                'ongoing'   => $ongoingSupplies->map($formatSupply),
                'archived'  => $archivedSupplies->map($formatSupply),
                'fulfilled' => $fulfilledSupplies->map($formatSupply),
            ],
            'total_supplies'                  => $farmer->supplies->count(),
            'total_quantity'                  => $farmer->supplies->sum(fn ($s) => $s->post->quantity_kg),
            'total_ongoing_supplies'          => $ongoingSupplies->count(),
            'total_ongoing_supplies_quantity' => $ongoingSupplies->sum(fn ($s) => $s->post->quantity_kg),
            'joined_at'                       => $farmer->created_at->format('M d, Y'),
            'joined_at_human'                 => $farmer->created_at->diffForHumans(),
        ];
    }

    public static function pending(): array
    {
        return FarmerProfile::with([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
        ])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($farmer) => [
                'id'   => $farmer->id,
                'user' => [
                    'id'           => $farmer->user->id,
                    'name'         => $farmer->user->name,
                    'email'        => $farmer->user->email,
                    'phone_number' => $farmer->user->phone_number,
                    'avatar_url'   => $farmer->user->getFirstMediaUrl('avatar'), // was: image_path (column removed)
                ],
                'location' => [
                    'province'     => $farmer->province->name,
                    'municipality' => $farmer->municipality->name,
                    'barangay'     => $farmer->barangay->name,
                    'coordinates'  => [
                        'lat' => (float) $farmer->latitude,
                        'lng' => (float) $farmer->longitude,
                    ],
                ],
                'farm_url'           => $farmer->getFirstMediaUrl('farm_photo'),
                'submitted_at'       => $farmer->created_at->format('M d, Y g:i A'),
                'submitted_at_human' => $farmer->created_at->diffForHumans(),
            ])
            ->toArray();
    }
}
