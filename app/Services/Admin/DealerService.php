<?php

namespace App\Services\Admin;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DealerService
{
    public function summary(): array
    {
        $newDealersThisMonth = DealerProfile::approved()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newDemandsThisMonth = DealerDemand::where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total_dealers' => DealerProfile::approved()->count(),
            'new_dealers_this_month' => $newDealersThisMonth,
            'total_demands' => DealerDemand::count(),
            'new_demands_this_month' => $newDemandsThisMonth,
        ];
    }

    public function paginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = DealerProfile::with([
            'user.media',
            'demands' => fn ($query) => $query
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('transaction_date', 'asc'),
        ])
            ->approved();

        if ($search) {
            $query->whereHas('user', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
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
                        'avatar_url' => $dealer->user->getFirstMediaUrl('avatar'),
                    ],
                    // Document is on a private disk — never a raw path.
                    // Serve via: GET /admin/dealers/{dealer}/document (admin middleware).
                    'document_url' => route('admin.dealers.document', $dealer->id),
                    'ongoing_demands_count' => $ongoingDemands->count(),
                    'ongoing_demands' => $ongoingDemands->map(fn ($demand) => [
                        'id' => $demand->id,
                        'variety' => [
                            'id' => $demand->post->variety->id,
                            'name' => $demand->post->variety->vegetable->name.' '.$demand->post->variety->name,
                            'category' => $demand->post->variety->vegetable->category->name,
                            'image_url' => $demand->post->variety->getFirstMediaUrl('variety_image'),
                        ],
                        'quantity_kg' => $demand->post->quantity_kg,
                        'transaction_date' => $demand->transaction_date->format('M d, Y'),
                    ]),
                    'joined_at' => $dealer->created_at->format('M d, Y'),
                    'joined_at_human' => $dealer->created_at->diffForHumans(),
                ];
            });
    }

    public function details(int $dealerId): ?array
    {
        $dealer = DealerProfile::query()
            ->where('is_approved', true)
            ->with([
                'user.media',
                'demands' => fn ($q) => $q
                    ->whereHas('post', fn ($q) => $q->ongoing())
                    ->with(['post.variety.media', 'post.variety.vegetable.category'])
                    ->orderBy('transaction_date', 'asc'),
            ])
            ->find($dealerId);

        if (! $dealer) {
            return null;
        }

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
                'avatar_url' => $dealer->user->getFirstMediaUrl('avatar'),
            ],
            'document_url' => route('admin.dealers.document', $dealer->id), // was: documentation_image (wrong key + removed column)
            'ongoing_demands' => $dealer->demands->map(fn ($demand) => [
                'id' => $demand->id,
                'variety' => [
                    'id' => $demand->post->variety->id,
                    'name' => $demand->post->variety->vegetable->name.' '.$demand->post->variety->name,
                    'category' => $demand->post->variety->vegetable->category->name,
                    'image_url' => $demand->post->variety->getFirstMediaUrl('variety_image'),
                ],
                'quantity_kg' => $demand->post->quantity_kg,
                'created_at' => $demand->created_at->format('M d, Y'),
                'transaction_date' => $demand->transaction_date->format('M d, Y'),
            ])->toArray(),
            'statistics' => [
                'total_ongoing_demands' => $ongoingDemands->count(),
                'total_quantity' => $ongoingDemands->sum(fn ($d) => $d->post->quantity_kg),
            ],
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }

    public function show(int $dealerId): ?array
    {
        $dealer = DealerProfile::with([
            'user.media',
            'demands' => fn ($query) => $query
                ->whereHas('post')
                ->with(['post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('transaction_date', 'desc'),
        ])
            ->where('is_approved', true)
            ->find($dealerId);

        if (! $dealer) {
            return null;
        }

        $formatDemand = fn ($demand) => [
            'id' => $demand->id,
            'variety' => [
                'id' => $demand->post->variety->id,
                'name' => $demand->post->variety->vegetable->name.' '.$demand->post->variety->name,
                'category' => $demand->post->variety->vegetable->category->name,
                'image_url' => $demand->post->variety->getFirstMediaUrl('variety_image'),
            ],
            'title' => $demand->post->title,
            'quantity_kg' => (float) $demand->post->quantity_kg,   // was: $demand->quantity_kg (bug)
            'offered_price' => (float) $demand->post->offered_price,
            'price_flag' => $demand->post->price_flag,
            'transaction_date' => $demand->transaction_date->format('M d, Y'),
            'status' => $demand->post->status,
            'created_at' => $demand->created_at->format('M d, Y'),
            'created_at_human' => $demand->created_at->diffForHumans(),  // was: 'created_at_haman' (typo)
        ];

        $ongoingDemands = $dealer->demands->filter(fn ($d) => $d->post->status === PostStatus::Ongoing)->values();
        $archivedDemands = $dealer->demands->filter(fn ($d) => $d->post->status === PostStatus::Archived)->values();
        $fulfilledDemands = $dealer->demands->filter(fn ($d) => $d->post->status === PostStatus::Fulfilled)->values();

        return [
            'id' => $dealer->id,
            'user' => [
                'id' => $dealer->user->id,
                'name' => $dealer->user->name,
                'email' => $dealer->user->email,
                'phone_number' => $dealer->user->phone_number,
                'avatar_url' => $dealer->user->getFirstMediaUrl('avatar'),
            ],
            'document_url' => route('admin.dealers.document', $dealer->id), // was: document_image (column removed)
            'demands' => [
                'ongoing' => $ongoingDemands->map($formatDemand),
                'archived' => $archivedDemands->map($formatDemand),
                'fulfilled' => $fulfilledDemands->map($formatDemand),
            ],
            'total_demands' => $dealer->demands->count(),
            'total_quantity' => $dealer->demands->sum(fn ($d) => $d->post->quantity_kg),
            'total_ongoing_demands' => $ongoingDemands->count(),
            'total_ongoing_demands_quantity' => $ongoingDemands->sum(fn ($d) => $d->post->quantity_kg),
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }

    public function pending(): array
    {
        return DealerProfile::with(['user.media'])
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
                    'avatar_url' => $dealer->user->getFirstMediaUrl('avatar'), // was: image_path (column removed)
                ],
                'document_url' => route('admin.dealers.document', $dealer->id), // was: document_image (column removed)
                'submitted_at' => $dealer->created_at->format('M d, Y g:i A'),
                'submitted_at_human' => $dealer->created_at->diffForHumans(),
            ])
            ->toArray();
    }
}
