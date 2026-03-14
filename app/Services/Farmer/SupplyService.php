<?php

namespace App\Services\Farmer;

use App\Enums\PostStatus;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Variety;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SupplyService
{
    public function summary(int $farmerId): array
    {
        $query = FarmerSupply::query()->where('farmer_id', $farmerId);

        $totalOngoing = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Ongoing))
            ->count();

        $totalFulfilled = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Fulfilled))
            ->count();

        $totalArchived = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Archived))
            ->count();

        $expiringThisWeek = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Ongoing))
            ->whereBetween('expiration_date', [now(), now()->addWeek()])
            ->count();

        return [
            'total_ongoing'      => $totalOngoing,
            'total_fulfilled'    => $totalFulfilled,
            'total_archived'     => $totalArchived,
            'expiring_this_week' => $expiringThisWeek,
        ];
    }

    public function paginated(int $farmerId, PostStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerSupply::with([
            'farmer.user',
            'media',
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->where('farmer_id', $farmerId);

        match ($status) {
            PostStatus::Ongoing   => $query->whereHas('post', fn ($q) => $q->ongoing()),
            PostStatus::Archived  => $query->whereHas('post', fn ($q) => $q->archived()),
            PostStatus::Fulfilled => $query->whereHas('post', fn ($q) => $q->fulfilled()),
        };

        return $query
            ->orderBy('expiration_date', 'desc')
            ->paginate($perPage);
    }

    public function varietyOptions(): array
    {
        return cache()->remember('farmer_supply_variety_options', 3600, fn () =>
            Variety::with('vegetable.category')
                ->orderBy('name')
                ->get()
                ->groupBy(fn ($variety) => $variety->vegetable->category->name)
                ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                    'id'               => $variety->id,
                    'name'             => $variety->vegetable->name.' '.$variety->name,
                    'weeks_to_harvest' => $variety->weeks_to_harvest,
                ])->values()->toArray())
                ->toArray()
        );
    }
}
