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
            'total_ongoing' => (clone $query)->ongoing()->count(),
            'total_fulfilled' => (clone $query)->fulfilled()->count(),
            'total_archived' => (clone $query)->archived()->count(),
        ];
    }

    public function paginated(int $userId, PostStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = Post::supply()
            ->where('user_id', $userId)
            ->with(['media', 'vegetable.category']);

        match ($status) {
            PostStatus::Ongoing => $query->ongoing(),
            PostStatus::Archived => $query->archived(),
            PostStatus::Fulfilled => $query->fulfilled(),
        };

        return $query->orderBy('scheduled_date', 'desc')->paginate($perPage);
    }

    public function vegetableOptions(): array
    {
        return cache()->remember('farmer_supply_vegetable_options', 3600, fn () => Vegetable::with('category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($vegetable) => $vegetable->category->name)
            ->map(fn ($vegetables) => $vegetables->map(fn ($vegetable) => [
                'id' => $vegetable->id,
                'name' => $vegetable->name,
            ])->values()->toArray())
            ->toArray()
        );
    }
}
