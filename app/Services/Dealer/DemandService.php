<?php

namespace App\Services\Dealer;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DemandService
{
    public function needsAction(int $userId): Collection
    {
        return Post::demand()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn ($q) => $q->ongoing())
            ->whereDate('scheduled_date', '<=', today())
            ->with(['media', 'postItems' => fn ($q) => $q->ongoing()->with('vegetable')])
            ->orderBy('scheduled_date')
            ->get();
    }

    public function paginatedDemand(int $userId, PostItemStatus $status = PostItemStatus::Ongoing, int $perPage = 20): LengthAwarePaginator
    {
        return Post::demand()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn ($q) => $q->ofStatus($status))
            ->when(
                $status === PostItemStatus::Ongoing,
                fn ($q) => $q->whereDate('scheduled_date', '>', today()),
            )
            ->with(['media', 'postItems' => fn ($q) => $q->ofStatus($status)->with('vegetable')])
            ->when(
                $status === PostItemStatus::Ongoing,
                fn ($q) => $q->orderBy('scheduled_date'),
                fn ($q) => $q->latest('scheduled_date'),
            )
            ->paginate($perPage);
    }

    public function varietyOptions(): array
    {
        return cache()->remember('dealer_demand_variety_options', 3600, fn () => Vegetable::query()
            ->with('category')
            ->orderByRaw('variety_name IS NULL, variety_name')
            ->get()
            ->groupBy(fn (Vegetable $v) => $v->category->name)
            ->map(fn ($rows) => $rows->map(fn ($v) => [
                'id' => $v->id,
                'name' => $v->display_name,
            ])->values()->toArray())
            ->toArray()
        );
    }
}
