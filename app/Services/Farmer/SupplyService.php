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
            'total_ongoing' => (clone $itemQuery)->ongoing()->count(),
            'total_fulfilled' => (clone $itemQuery)->fulfilled()->count(),
            'total_expired' => (clone $itemQuery)->expired()->count(),
        ];
    }

    public function paginatedSupply(int $userId, PostItemStatus $status = PostItemStatus::Ongoing, int $perPage = 20): LengthAwarePaginator
    {
        return Post::supply()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn ($q) => $q->ofStatus($status))
            ->with([
                'media',
                'postItems' => fn ($q) => $q->ofStatus($status)->with('variety.vegetable'),
            ])
            ->when(
                $status === PostItemStatus::Ongoing,
                fn ($q) => $q->orderBy('scheduled_date'),
                fn ($q) => $q->latest('scheduled_date'),
            )
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
        return cache()->remember('farmer_supply_variety_options', 3600, fn () => Variety::with(['vegetable'])
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($v) => $v->vegetable->name)
            ->map(fn ($varieties) => $varieties->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->name,
            ])->values()->toArray())
            ->toArray()
        );
    }
}
