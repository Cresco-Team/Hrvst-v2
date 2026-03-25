<?php

namespace App\Services\Dealer;

use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Product\Variety;
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
        $query = Post::demand()
            ->where('user_id', $userId)
            ->with(['variety.media', 'variety.vegetable.category', 'variety.latestPrice']);

        match ($status) {
            PostStatus::Ongoing => $query->ongoing(),
            PostStatus::Archived => $query->archived(),
            PostStatus::Fulfilled => $query->fulfilled(),
        };

        return $query->orderBy('scheduled_date', 'desc')->paginate($perPage);
    }

    public function varietyOptions(): array
    {
        return cache()->remember('dealer_demand_variety_options', 3600, fn () => Variety::with('vegetable.category', 'latestPrice')
            ->get()
            ->groupBy(fn ($variety) => $variety->vegetable->category->name)
            ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                'id' => $variety->id,
                'name' => $variety->vegetable->name.' '.$variety->name,
                'current_price' => $variety->latestPrice ? [
                    'min' => (float) $variety->latestPrice->price_min,
                    'max' => (float) $variety->latestPrice->price_max,
                ] : null,
            ])->values()->toArray())
            ->toArray()
        );
    }
}
