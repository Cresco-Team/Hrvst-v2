<?php

namespace App\Services\Farmer;

use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerDemand::with([
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->whereHas('post', fn ($q) => $q->ongoing());

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('post.variety', function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('vegetable', fn (Builder $vq) =>
                        $vq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('post.variety.vegetable', fn (Builder $q) =>
                $q->where('category_id', $filters['category_id'])
            );
        }

        if (! empty($filters['variety_id'])) {
            $query->whereHas('post', fn ($q) => $q->where('variety_id', $filters['variety_id']));
        }

        if (! empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('transaction_date', 'asc')
            ->paginate($perPage);
    }

    public function detailed(DealerDemand $demand): array
    {
        // All variety/price/status data lives on Post, not on DealerDemand directly.
        // Previous code accessed $demand->variety, $demand->quantity_kg etc. — all wrong.
        $demand->load([
            'dealer.user.media',
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
            'post.reactions.user',
        ]);

        return [
            'id'     => $demand->id,
            'dealer' => [
                'id'           => $demand->dealer->id,
                'name'         => $demand->dealer->user->name,
                'phone_number' => $demand->dealer->user->phone_number,
                'avatar_url'   => $demand->dealer->user->getFirstMediaUrl('avatar'), // was: image_path (column removed)
            ],
            'variety' => [
                'id'        => $demand->post->variety_id,              // was: $demand->variety_id (bug)
                'name'      => $demand->post->variety->name,            // was: $demand->variety->name (bug)
                'vegetable' => $demand->post->variety->vegetable->name,
                'image_url' => $demand->post->variety->getFirstMediaUrl('variety_image'),
            ],
            'transaction_date'       => $demand->transaction_date->format('M d, Y'),
            'days_until_transaction' => now()->diffInDays($demand->transaction_date, false),
            'status'                 => $demand->post->status,          // was: $demand->status (bug)
            'quantity_kg'            => (float) $demand->post->quantity_kg,  // was: $demand->quantity_kg (bug)
            'offered_price'          => (float) $demand->post->offered_price, // was: $demand->price_offered (wrong key + bug)
            'price_flag'             => $demand->post->price_flag,
            'market_price'           => [
                'min' => (float) $demand->post->variety->latestPrice->price_min,
                'max' => (float) $demand->post->variety->latestPrice->price_max,
            ],
            'created_at'       => $demand->created_at->format('M d, Y g:i A'),
            'created_at_human' => $demand->created_at->diffForHumans(),
        ];
    }

    public function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.posts', fn (Builder $q) => $q
            ->ongoing()
            ->whereHasMorph('postable', DealerDemand::class, fn ($q) => $q
                ->where('transaction_date', '>=', now())
            )
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id'   => $category->id,
                'name' => $category->name,
            ])
            ->toArray();
    }
}
