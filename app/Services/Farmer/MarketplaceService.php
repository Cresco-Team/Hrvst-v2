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
