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
            ->with(['media', 'vegetable.category', 'farmerProfile.municipality']);

        if ($userId) {
            $query->withExists([
                'hearts as is_hearted' => fn (Builder $q) => $q->where('user_id', $userId),
            ]);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('vegetable', fn (Builder $q) => $q->where('name', 'LIKE', "%{$search}%"));
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('vegetable', fn (Builder $q) => $q->where('category_id', $filters['category_id']));
        }

        if (! empty($filters['vegetable_id'])) {
            $query->where('vegetable_id', $filters['vegetable_id']);
        }

        if (! empty($filters['municipality_id'])) {
            $query->whereHas('farmerProfile', fn (Builder $q) => $q->where('municipality_id', $filters['municipality_id']));
        }

        return $query->orderBy('scheduled_date', 'asc')->paginate($perPage);
    }

    public function categoryOptions(): array
    {
        return Category::whereHas(
            'vegetables.posts',
            fn (Builder $q) => $q->ongoing()->supply()->where('scheduled_date', '>=', now())
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])
            ->toArray();
    }
}
