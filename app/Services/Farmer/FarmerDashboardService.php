<?php

namespace App\Services\Farmer;

use App\Models\Marketplace\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FarmerDashboardService
{
    public function expiringSupplies(int $userId): Collection
    {
        return Post::supply()
            ->where('user_id', $userId)
            ->whereHas('postItems', fn (Builder $q) => $q->ongoing())
            ->whereBetween('scheduled_date', [
                today()->subDays(Post::ACTION_WINDOW_DAYS - 1),
                today(),
            ])
            ->with(['postItems' => fn ($q) => $q->ongoing()->with('vegetable')])
            ->orderBy('scheduled_date')
            ->get();
    }
}
