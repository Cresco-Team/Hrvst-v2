<?php

namespace App\Services\Farmer;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class SupplyService
{
    public function summary(int $userId): array
    {
        $itemQuery = PostItem::whereHas('post', fn (Builder $q) => $q->supply()->where('user_id', $userId));

        return [
            'total_growing' => Post::supply()->growing()->where('user_id', $userId)->count(),
            'total_ongoing' => (clone $itemQuery)->ongoing()->count(),
            'total_fulfilled' => (clone $itemQuery)->fulfilled()->count(),
            'total_archived' => (clone $itemQuery)->archived()->count(),
        ];
    }

    public function paginatedGrowing(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return Post::supply()
            ->where('user_id', $userId)
            ->growing()
            ->with(['media', 'vegetable.category'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function paginatedHarvested(int $userId, PostItemStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        return PostItem::query()
            ->select('post_items.*')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->with([
                'variety.vegetable.category',
                'variety.media',
                'post',
            ])
            ->whereHas('post', fn (Builder $q) => $q->supply()->harvested()->where('user_id', $userId))
            ->ofStatus($status)
            ->whereNull('post_items.deleted_at')
            ->orderBy('posts.scheduled_date', 'desc')
            ->paginate($perPage);
    }

    public function vegetableOptions(): array
    {
        return cache()->remember('farmer_supply_vegetable_options', 3600, fn () => Vegetable::with('category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($v) => $v->category->name)
            ->map(fn ($vegetables) => $vegetables->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
            ])->values()->toArray())
            ->toArray()
        );
    }

    public function varietyOptions(): array
    {
        return cache()->remember('farmer_harvest_variety_options', 3600, fn () => Variety::with(['vegetable', 'latestPrice'])
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($v) => $v->vegetable->name)
            ->map(fn ($varieties) => $varieties->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
                'current_price' => $v->latestPrice ? [
                    'min' => (float) $v->latestPrice->price_min,
                    'max' => (float) $v->latestPrice->price_max,
                ] : null,
            ])->values()->toArray())
            ->toArray()
        );
    }
}
