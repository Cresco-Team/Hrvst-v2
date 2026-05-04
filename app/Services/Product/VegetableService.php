<?php

namespace App\Services\Product;

use App\Models\Product\Vegetable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class VegetableService
{
    public function paginated(
        ?string $categoryId = null,
        ?string $search = null,
        ?string $priceFilter = null,
        ?int $userId = null,
    ): Builder {
        return Vegetable::with([
            'category',
            'varieties' => function (HasMany $varieties) use ($search, $priceFilter, $userId): void {
                $varieties
                    ->with(['latestPrice', 'lastTwoPrices', 'media'])
                    // supply_count / demand_count now counted via post_items
                    // because posts.variety_id was removed; variety-level granularity
                    // lives in post_items.variety_id
                    ->withCount([
                        'postItems as supply_count' => fn (Builder $q) => $q->whereHas(
                            'post', fn (Builder $p) => $p->supply()->ongoing()
                        ),
                        'postItems as demand_count' => fn (Builder $q) => $q->whereHas(
                            'post', fn (Builder $p) => $p->demand()->ongoing()
                        ),
                    ])
                    ->when($userId, fn (Builder $q) => $q->withExists([
                        'hearts as is_hearted' => fn (Builder $q) => $q->where('user_id', $userId),
                    ]))
                    ->search($search)
                    ->when(
                        $priceFilter === 'no_price',
                        fn ($q) => $q->whereDoesntHave('latestPrice')
                    )
                    ->when(
                        $priceFilter && $priceFilter !== 'no_price',
                        fn ($q) => $this->applyPriceFilter($q, $priceFilter)
                    )
                    ->orderBy('name');
            },
        ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->search($search)
            ->withCount('varieties')
            ->orderBy('name');
    }

    public function summary(): array
    {
        $oneWeekAgo = now()->subWeek();
        $oneMonthAgo = now()->subMonth();

        $priceStats = DB::table('varieties')
            ->leftJoin(
                DB::raw('(SELECT variety_id, MAX(recorded_at) as latest FROM price_histories GROUP BY variety_id) as ph_max'),
                'varieties.id', '=', 'ph_max.variety_id'
            )
            ->leftJoin('price_histories as ph', function ($join) {
                $join->on('ph.variety_id', '=', 'varieties.id')
                    ->on('ph.recorded_at', '=', 'ph_max.latest');
            })
            ->selectRaw('
                COUNT(varieties.id) as total_varieties,
                SUM(CASE WHEN ph.recorded_at >= ? THEN 1 ELSE 0 END) as updated_week,
                SUM(CASE WHEN ph.recorded_at >= ? AND ph.recorded_at < ? THEN 1 ELSE 0 END) as updated_month,
                SUM(CASE WHEN ph.recorded_at < ? AND ph.recorded_at IS NOT NULL THEN 1 ELSE 0 END) as stale,
                SUM(CASE WHEN ph.id IS NULL THEN 1 ELSE 0 END) as no_price
            ', [$oneWeekAgo, $oneMonthAgo, $oneMonthAgo, $oneMonthAgo])
            ->first();

        return [
            'total_varieties' => (int) $priceStats->total_varieties,
            'total_vegetables' => Vegetable::count(),
            'price_stats' => [
                'updated_week' => (int) $priceStats->updated_week,
                'updated_month' => (int) $priceStats->updated_month,
                'stale' => (int) $priceStats->stale,
                'no_price' => (int) $priceStats->no_price,
            ],
        ];
    }

    /**
     * Applies a date-based price filter by constraining to varieties
     * that have a latestPrice record within the given window.
     *
     * Bug fix: original applied ->where('recorded_at', ...) directly on the
     * variety builder which had no recorded_at column — must scope via relationship.
     */
    private function applyPriceFilter(Builder $query, string $filter): void
    {
        match ($filter) {
            'week' => $query->whereHas(
                'latestPrice', fn (Builder $q) => $q->where('recorded_at', '>=', now()->subWeek())
            ),
            'month' => $query->whereHas(
                'latestPrice', fn (Builder $q) => $q->where('recorded_at', '>=', now()->subMonth())
            ),
            'stale' => $query->whereHas(
                'latestPrice', fn (Builder $q) => $q->where('recorded_at', '<', now()->subMonth())
            ),
            default => null,
        };
    }
}
