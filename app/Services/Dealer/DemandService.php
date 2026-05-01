<?php

namespace App\Services\Dealer;

use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DemandService
{
    public function summary(int $userId): array
    {
        $query = Post::demand()->where('user_id', $userId);

        return [
            'total_ongoing' => (clone $query)->ongoing()->count(),
            'total_fulfilled' => (clone $query)->fulfilled()->count(),
            'total_archived' => (clone $query)->archived()->count(),
        ];
    }

    public function paginated(int $userId, PostStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        return Post::demand()
            ->where('user_id', $userId)
            ->ofStatus($status)
            ->with(['vegetable.category', 'postItems.variety'])
            ->orderBy('scheduled_at', 'desc')
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

    /**
     * Varieties grouped by vegetable for the demand item form.
     * Includes latest market price hint per variety.
     */
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
