<?php

namespace App\Services\Dealer;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
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
            'total_expired' => (clone $query)->expired()->count(),
        ];
    }

    public function paginated(int $userId, PostItemStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        return Post::demand()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn (Builder $q) => $q->ofStatus($status))
            ->with([
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
        return cache()->remember('dealer_demand_variety_options', 3600, fn () => Variety::with(['vegetable'])
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
