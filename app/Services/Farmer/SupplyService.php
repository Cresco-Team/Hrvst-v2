<?php

namespace App\Services\Farmer;

use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplyService
{
    public function summary(int $userId): array
    {
        $query = Post::supply()->where('user_id', $userId);

        return [
            'total_growing' => (clone $query)->growing()->count(),
            'total_ongoing' => (clone $query)->ongoing()->count(),
            'total_fulfilled' => (clone $query)->fulfilled()->count(),
            'total_archived' => (clone $query)->archived()->count(),
        ];
    }

    public function paginated(int $userId, PostStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        return Post::supply()
            ->where('user_id', $userId)
            ->ofStatus($status)
            ->with(['media', 'vegetable.category', 'postItems.variety'])
            ->orderBy('created_at', 'desc')
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
}
