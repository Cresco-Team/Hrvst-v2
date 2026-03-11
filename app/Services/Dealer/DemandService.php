<?php

namespace App\Services\Dealer;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Variety;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DemandService
{
    public function summary(int $dealerId): array
    {
        $query = DealerDemand::query()->where('dealer_id', $dealerId);

        $totalOngoing = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Ongoing))
            ->count();

        $totalFulfilled = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Fulfilled))
            ->count();

        $totalArchived = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Archived))
            ->count();

        $upcomingTransactions = (clone $query)
            ->whereHas('post', fn ($q) => $q->where('status', PostStatus::Ongoing))
            ->whereBetween('transaction_date', [now(), now()->addWeek()])
            ->count();

        return [
            'total_ongoing'         => $totalOngoing,
            'total_fulfilled'       => $totalFulfilled,
            'total_archived'        => $totalArchived,
            'upcoming_transactions' => $upcomingTransactions,
        ];
    }

    public function paginated(int $dealerId, PostStatus $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerDemand::with([
            'dealer.user',
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->where('dealer_id', $dealerId);

        match ($status) {
            PostStatus::Ongoing   => $query->whereHas('post', fn ($q) => $q->ongoing()),
            PostStatus::Archived  => $query->whereHas('post', fn ($q) => $q->archived()),
            PostStatus::Fulfilled => $query->whereHas('post', fn ($q) => $q->fulfilled()),
        };

        return $query
            ->orderBy('transaction_date', 'desc')
            ->paginate($perPage)
            ->through(fn (DealerDemand $demand) => [
                'id'     => $demand->id,
                'dealer' => [
                    'id'   => $demand->dealer->id,
                    'name' => $demand->dealer->user->name,
                ],
                'variety' => [
                    'id'        => $demand->post->variety->id,
                    'name'      => $demand->post->variety->name,
                    'vegetable' => $demand->post->variety->vegetable->name,
                    'image_url' => $demand->post->variety->getFirstMediaUrl('variety_image'),
                ],
                'title'                  => $demand->post->title,
                'quantity_kg'            => (float) $demand->post->quantity_kg,
                'offered_price'          => (float) $demand->post->offered_price,
                'price_flag'             => $demand->post->price_flag,
                'transaction_date'       => $demand->transaction_date->format('M d, Y'),
                'days_until_transaction' => $demand->days_until_transaction,
                'status'                 => $demand->post->status,
                'created_at_human'       => $demand->created_at->diffForHumans(),
            ]);
    }

    public function varietyOptions(): array
    {
        return cache()->remember('dealer_demand_variety_options', 3600, fn () =>
            Variety::with('vegetable.category', 'latestPrice')
                ->get()
                ->groupBy(fn ($variety) => $variety->vegetable->category->name)
                ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                    'id'            => $variety->id,
                    'name'          => $variety->vegetable->name.' '.$variety->name,
                    'current_price' => $variety->latestPrice ? [
                        'min' => (float) $variety->latestPrice->price_min,
                        'max' => (float) $variety->latestPrice->price_max,
                    ] : null,
                ])->values()->toArray())
                ->toArray()
        );
    }
}
