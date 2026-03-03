<?php

namespace App\Services\Admin;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DealerService
{
    public static function summary(): array
    {
        $newDealersThisMonth = DealerProfile::approved()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newDemandsThisMonth = DealerDemand::where('created_at', now()->startOfMonth())
            ->count();

        return [
            'total_dealers' => DealerProfile::approved()->count(),
            'new_dealers_this_month' => $newDealersThisMonth,
            'total_demands' => DealerDemand::count(),
            'new_demands_this_month' => $newDemandsThisMonth,
        ];
    }

    public static function paginated(int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        return DealerProfile::with([
            'user',
            'demands' => fn($query) => $query
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['post.variety.vegetable.category'])
                ->orderBy('transaction_date', 'asc')
            ])
            ->approved()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($dealer) {

                $ongoingDemands = $dealer->demands->filter(
                    fn ($demand) => $demand->post->status === PostStatus::Ongoing
                );

                return [
                    'id' => $dealer->id,
                    'user' => [
                        'id' => $dealer->user->id,
                        'name' => $dealer->user->name,
                        'email' => $dealer->user->email,
                        'phone_number' => $dealer->user->phone_number,
                        'image_url' => $dealer->user->image_url,
                    ],
                    'document_image' => $dealer->document_image,
                    'ongoing_demands_count' => $ongoingDemands->count(),
                    'ongoing_demands' => $ongoingDemands->map(fn($demand) => [
                        'id' => $demand->id,
                        'variety' => [
                            'id' => $demand->post->variety->id,
                            'name' => $demand->post->variety->name,
                            'category' => $demand->post->variety->vegetable->category->name,
                            'image_url' => $demand->post->variety->image_url,
                        ],
                        'quantity_kg' => $demand->post->quantity_kg,
                        'transaction_date' => $demand->transaction_date->format('M d, Y'),
                    ]),
                    'joined_at' => $dealer->created_at->format('M d, Y'),
                    'joined_at_human' => $dealer->created_at->diffForHumans(),
                ];
            }
        );
    }

    public static function details(int $dealerId): ?array
    {
        $dealer = DealerProfile::query()
            ->where('is_approved', true)
            ->with([
                'user',
                'demands' => fn($q) => $q->whereHas('post', fn ($q) => $q->ongoing())
                    ->with(['post.variety.vegetable.category'])
                    ->orderBy('transaction_date', 'asc'),
            ])->find($dealerId);

        if (! $dealer) return null;

        $ongoingDemands = $dealer->demands->filter(
            fn ($demand) => $demand->post->status === PostStatus::Ongoing
        );

        return [
            'id' => $dealer->id,
            'user' => [
                'id' => $dealer->user->id,
                'name' => $dealer->user->name,
                'email' => $dealer->user->email,
                'phone_number' => $dealer->user->phone_number,
                'image_url' => $dealer->user->image_url,
            ],
            'documentation_image' => $dealer->documentation_image,
            'ongoing_demands' => $dealer->demands->map(fn($demand) => [
                'id' => $demand->id,
                'variety' => [
                    'id' => $demand->post->variety->id,
                    'name' => $demand->post->variety->vegetable->name . ' ' . $demand->post->variety->name,
                    'category' => $demand->post->variety->vegetable->category->name,
                    'image_url' => $demand->post->variety->image_url,
                ],
                'quantity_kg' => $demand->post->quantity_kg,
                'created_at' => $demand->created_at->format('M d, Y'),
                'transaction_date' => $demand->transaction_date->format('M d, Y'),
            ])->toArray(),
            'statistics' => [
                'total_ongoing_demands' => $ongoingDemands->count(),
                'total_quantity' => $ongoingDemands->sum('quantity_kg'),
            ],
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }

    public static function show(int $dealerId): ?array
    {
        $dealer = DealerProfile::with([
            'user',
            'demands' => fn($query) => $query->whereHas('post')->with(['post.variety.vegetable.category'])
                ->orderBy('transaction_date', 'desc'),
        ])
            ->where('is_approved', true)
            ->find($dealerId);

        if (!$dealer) {
            return null;
        }

        $formatDemand = fn ($demand) => [
            'id' => $demand->id,
            'variety' => [
                'id' => $demand->post->variety->id,
                'name' => $demand->post->variety->vegetable->name.' '.$demand->post->variety->name,
                'category' => $demand->post->variety->vegetable->category->name,
                'image_url' => $demand->post->variety->image_url,
            ],
            'title' => $demand->post->title,
            'quantity_kg' => (float) $demand->quantity_kg,
            'offered_price' => (float) $demand->post->offered_price,
            'price_flag' => $demand->post->price_flag,
            'transaction_date' => $demand->transaction_date->format('M d, Y'),
            'status' => $demand->post->status,
            'created_at' => $demand->created_at->format('M d, Y'),
            'created_at_haman' => $demand->created_at->diffForHumans(),
        ];

        $ongoingDemands = $dealer->demands
            ->filter(fn ($demand) => $demand->post->status === PostStatus::Ongoing)
            ->values();

        $archivedDemands = $dealer->demands
            ->filter(fn ($demand) => $demand->post->status === PostStatus::Archived)
            ->values();

        $fulfilledDemands = $dealer->demands
            ->filter(fn ($demand) => $demand->post->status === PostStatus::Fulfilled)
            ->values();

        return [
            'id' => $dealer->id,
            'user' => [
                'id' => $dealer->user->id,
                'name' => $dealer->user->name,
                'email' => $dealer->user->email,
                'phone_number' => $dealer->user->phone_number,
                'image_url' => $dealer->user->image_url,
            ],
            'document_image' => $dealer->document_image,
            'demands' => [
                'ongoing' => $ongoingDemands->map($formatDemand),
                'archived' => $archivedDemands->map($formatDemand),
                'fulfilled' => $fulfilledDemands->map($formatDemand),
            ],
            'total_demands' => $dealer->demands->count(),
            'total_quantity' => $dealer->demands->sum(fn ($demand) => $demand->post->quantity_kg),
            'total_ongoing_demands' => $ongoingDemands->count(),
            'total_ongoing_demands_quantity' => $ongoingDemands->sum(fn($demand) => $demand->post->quantity_kg),
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }

    public static function pending(): array
    {
        return DealerProfile::with(['user'])
            ->where('is_approved', false)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($dealer) => [
                'id' => $dealer->id,
                'user' => [
                    'id' => $dealer->user->id,
                    'name' => $dealer->user->name,
                    'email' => $dealer->user->email,
                    'phone_number' => $dealer->user->phone_number,
                    'image_path' => $dealer->user->image_path,
                ],
                'document_image' => $dealer->document_image,
                'submitted_at' => $dealer->created_at->format('M d, Y g:i A'),
                'submitted_at_human' => $dealer->created_at->diffForHumans(),
            ])
            ->toArray();
    }
}
