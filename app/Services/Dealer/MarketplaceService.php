<?php

namespace App\Services\Dealer;

use App\Models\Marketplace\Post;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public function paginated(array $filters = [], int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        $query = Post::supply()
            ->ongoing()
            ->with(['media', 'variety.media', 'variety.vegetable.category', 'variety.latestPrice', 'farmerProfile.municipality']);

        if ($userId) {
            $query->withExists([
                'hearts as is_hearted' => fn (Builder $q) => $q->where('user_id', $userId),
            ]);
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('variety.vegetable', fn (Builder $q) => $q->where('category_id', $filters['category_id'])
            );
        }

        if (! empty($filters['variety_id'])) {
            $query->where('variety_id', $filters['variety_id']);
        }

        if (! empty($filters['municipality_id'])) {
            $query->whereHas('farmerProfile', fn (Builder $q) => $q->where('municipality_id', $filters['municipality_id'])
            );
        }

        return $query->orderBy('scheduled_date', 'asc')->paginate($perPage);
    }

    public function categoryOptions(): array
    {
        return Category::whereHas(
            'vegetables.varieties.posts',
            fn (Builder $q) => $q
                ->ongoing()
                ->supply()
                ->where('scheduled_date', '>=', now())
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])
            ->toArray();
    }
}
