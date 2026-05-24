<?php

namespace App\Services\Dealer;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DemandService
{
    public function summary(int $userId): array
    {
        $query = PostItem::whereHas('post', fn (Builder $q) => $q->demand()->where('user_id', $userId));

        return [
            'total_ongoing' => (clone $query)->ongoing()->count(),
            'total_fulfilled' => (clone $query)->fulfilled()->count(),
            'total_unsettled' => (clone $query)->unsettled()->count(),
        ];
    }

    public function paginated(int $userId, PostItemStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        return PostItem::query()
            ->select('post_items.*')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->with(['variety.vegetable.category', 'post'])
            ->whereHas('post', fn (Builder $q) => $q->demand()->where('user_id', $userId))
            ->where('post_items.status', $status)
            ->whereNull('post_items.deleted_at')
            ->orderBy('posts.scheduled_date', 'desc')
            ->paginate($perPage);
    }

    public function vegetableOptions(): array
    {
        return cache()->remember('dealer_demand_vegetable_options', 3600, fn () => Vegetable::with('category')
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
        return cache()->remember('dealer_demand_variety_options', 3600, fn () => Variety::with(['vegetable', 'latestPrice'])
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
