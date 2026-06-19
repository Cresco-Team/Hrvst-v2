<?php

namespace App\Services\Farmer;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public function paginated(array $filters = [], int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        return PostItem::query()
            ->select('post_items.*')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->with([
                'variety.vegetable.category',
            ])
            ->ongoing()
            ->whereHas('post', fn (Builder $q) => $q->demand()->ready())
            ->whereNull('post_items.deleted_at')
            ->when(! empty($filters['search']), fn (Builder $q) => $q->whereHas(
                'variety', fn (Builder $q) => $q
                    ->where('name', 'LIKE', "%{$filters['search']}%")
                    ->orWhereHas('vegetable', fn (Builder $q) => $q->where('name', 'LIKE', "%{$filters['search']}%"))
            ))
            ->when(! empty($filters['category_id']), fn (Builder $q) => $q->whereHas(
                'variety.vegetable', fn (Builder $q) => $q->where('category_id', $filters['category_id'])
            ))
            ->when(! empty($filters['variety_id']), fn (Builder $q) => $q->where('post_items.variety_id', $filters['variety_id']))
            ->when(! empty($filters['date_from']), fn (Builder $q) => $q->where('posts.scheduled_date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn (Builder $q) => $q->where('posts.scheduled_date', '<=', $filters['date_to']))
            ->orderBy('posts.scheduled_date', 'asc')
            ->paginate($perPage);
    }

    public function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.postItems', fn (Builder $q) => $q
            ->where('status', PostItemStatus::Ongoing)
            ->whereHas('post', fn (Builder $q) => $q->demand()->ready())
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => ['id' => $category->id, 'name' => $category->name])
            ->toArray();
    }
}
