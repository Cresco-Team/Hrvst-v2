<?php

namespace App\Services\Farmer;

use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerDemand::with([
            'dealer.user',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->whereHas('post', fn ($q) => $q->ongoing());

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('post.variety', function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('vegetable', fn(Builder $vq) => 
                        $vq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('post.variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $filters['category_id'])
            );
        }

        if (!empty($filters['variety_id'])) {
            $query->whereHas('post', fn ($q) => $q
                ->where('variety_id', $filters['variety_id'])
            );
        }

        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('transaction_date', 'asc')
            ->paginate($perPage)
            ->through(function ($demand) {
                return [
                    'id' => $demand->id,
                    'dealer' => [
                        'id' => $demand->dealer->id,
                        'name' => $demand->dealer->user->name,
                        'phone_number' => $demand->dealer->user->phone_number,
                        'image_url' => $demand->dealer->user->image_url,
                    ],
                    'variety' => [
                        'id' => $demand->post->variety_id,
                        'name' => $demand->post->variety->name,
                        'vegetable' => $demand->post->variety->vegetable->name,
                        'image_url' => $demand->post->variety->image_url,
                    ],
                    'title' => $demand->post->title,
                    'quantity_kg' => (float) $demand->post->quantity_kg,
                    'offered_price' => (float) $demand->post->offered_price,
                    'price_flag' => $demand->post->price_flag,
                    'transaction_date' => $demand->transaction_date->format('M d, Y'),
                    'days_until_transaction' => now()->diffInDays($demand->transaction_date, false),
                    'status' => $demand->post->status,
                    'created_at_human' => $demand->created_at->diffForHumans(),
                ];
            });
    }

    public static function detailed(DealerDemand $demand): array
    {
        $demand->load([
            'dealer.user',
            'variety.vegetable.category',
            'variety.latestPrice',
            'post.reactions.user',
        ]);

        return [
            'id' => $demand->id,
            'dealer' => [
                'id' => $demand->dealer->id,
                'name' => $demand->dealer->user->name,
                'phone_number' => $demand->dealer->user->phone_number,
                'image_path' => $demand->dealer->user->image_path,
            ],
            'transaction_date' => $demand->transaction_date->format('M d, Y'),
            'days_until_transaction' => now()->diffInDays($demand->transaction_date, false),
            'status' => $demand->status,
            'variety' => [
                'id' => $demand->variety_id,
                'name' => $demand->variety->name,
                'vegetable' => $demand->variety->vegetable->name,
                'image_url' => $demand->variety->image_url,
            ],
            'quantity_kg' => (float) $demand->quantity_kg,
            'price_offered' => (float) $demand->price_offered,
            'price_flag' => $demand->post->price_flag,
            'market_price' => [
                'min' => (float) $demand->variety->latestPrice->price_min,
                'max' => (float) $demand->variety->latestPrice->price_max,
            ],
            'created_at' => $demand->created_at->format('M d, Y g:i A'),
            'created_at_human' => $demand->created_at->diffForHumans(),
        ];
    }

    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.posts', fn (Builder $q) => $q
            ->ongoing()
            ->whereHasMorph('postable', DealerDemand::class, fn ($q) => $q
                ->where('transaction_date', '>=', now())
            )
        )->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->toArray();
    }
}
