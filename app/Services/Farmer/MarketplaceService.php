<?php

namespace App\Services\Farmer;

use App\Models\Marketplace\Post;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public function paginated(array $filters = [], int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        $query = Post::demand()
            ->ongoing()
            ->with(['vegetable.category']);

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

        if (! empty($filters['date_from'])) {
            $query->where('scheduled_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('scheduled_date', '<=', $filters['date_to']);
        }

        return $query->orderBy('scheduled_date', 'asc')->paginate($perPage);
    }

    public function categoryOptions(): array
    {
        return Category::whereHas(
            'vegetables.posts',
            fn (Builder $q) => $q->ongoing()->demand()->where('scheduled_date', '>=', now())
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])
            ->toArray();
    }
}
