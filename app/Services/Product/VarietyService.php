<?php

namespace App\Services\Product;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Http\Resources\Product\VegetableResource;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class VarietyService
{
    public function __construct(
        private VarietyActivityService $activityService,
        private VarietyCalendarService $calendarService,
    ) {}

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
     * Vegetable-grouped query used by both:
     *   - Admin table      → $perPage = null  → returns plain resolved array
     *   - Marketplace catalog → $perPage = N  → returns paginated envelope array
     *
     * When paginating, priceFilter is intentionally excluded — the marketplace
     * has no price-filter UI. Pass it only from admin callers.
     */
    public function table(
        ?string $search = null,
        ?string $priceFilter = null,
        ?int $categoryId = null,
        ?int $userId = null,
        ?int $perPage = null,
    ): array {
        $query = Vegetable::with([
            'category',
            'varieties' => function (HasMany $q) use ($search, $priceFilter, $userId): void {
                $q->with(['latestPrice', 'lastTwoPrices', 'media'])
                    ->withCount([
                        'posts as supply_count' => fn (Builder $q) => $q->supply(),
                        'posts as demand_count' => fn (Builder $q) => $q->demand(),
                    ])
                    ->when($userId, fn (Builder $q) => $q->withExists([
                        'hearts as is_hearted' => fn (Builder $q) => $q->where('user_id', $userId),
                    ]))
                    ->orderBy('name');

                if ($search) {
                    $q->where('name', 'like', "%{$search}%");
                }

                if ($priceFilter) {
                    if ($priceFilter === 'no_price') {
                        $q->whereDoesntHave('latestPrice');
                    } else {
                        $q->whereHas('latestPrice', function (Builder $q) use ($priceFilter) {
                            $this->applyPriceFilter($q, $priceFilter);
                        });
                    }
                }
            },
        ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->withCount('varieties')
            ->orderBy('name');

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('varieties', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($priceFilter) {
            if ($priceFilter === 'no_price') {
                $query->whereHas('varieties', fn (Builder $q) => $q->whereDoesntHave('latestPrice'));
            } else {
                $query->whereHas('varieties', function (Builder $q) use ($priceFilter): void {
                    $q->whereHas('latestPrice', function (Builder $q) use ($priceFilter) {
                        $this->applyPriceFilter($q, $priceFilter);
                    });
                });
            }
        }

        // Paginated path — marketplace catalog
        if ($perPage !== null) {
            $paginator = $query->whereHas('varieties')->paginate($perPage)->withQueryString();

            return VegetableResource::collection($paginator)->response()->getData(true);
        }

        // Flat path — admin table (existing behaviour)
        return VegetableResource::collection($query->get())->resolve();
    }

    public function forCatalog(int $perPage = 20, ?string $search = null, ?int $categoryId = null, ?int $userId = null): LengthAwarePaginator
    {
        return Variety::with(['vegetable.category', 'latestPrice', 'lastTwoPrices', 'media'])
            ->withCount([
                'posts as supply_count' => fn (Builder $q) => $q->supply()->ongoing(),
                'posts as demand_count' => fn (Builder $q) => $q->demand()->ongoing(),
            ])
            ->when($search, fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('vegetable', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            )
            ->when($categoryId, fn (Builder $q) => $q->whereHas(
                'vegetable', fn ($q) => $q->where('category_id', $categoryId)
            ))
            ->when($userId, fn (Builder $q) => $q->withExists([
                'hearts as is_hearted' => fn (Builder $q) => $q->where('user_id', $userId),
            ]))
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function show(Variety $variety, int $year, int $month): Variety
    {
        $variety->load(['vegetable.category', 'latestPrice', 'recentPrices', 'media'])
            ->loadCount([
                'posts as supply_count' => fn (Builder $q) => $q->supply()->ongoing(),
                'posts as demand_count' => fn (Builder $q) => $q->demand()->ongoing(),
            ])
            ->loadSum([
                'posts as monthly_supply_kg' => fn (Builder $q) => $q
                    ->where('type', PostType::Supply->value)
                    ->where('created_at', '>=', now()->startOfMonth()),
                'posts as monthly_demand_kg' => fn (Builder $q) => $q
                    ->where('type', PostType::Demand->value)
                    ->where('created_at', '>=', now()->startOfMonth()),
            ], 'quantity_kg');

        $variety->supply_municipalities = $this->resolveSupplyMunicipalities($variety->id);
        $variety->monthly_activity = $this->activityService->buildMonthlyActivity($variety->id);
        $variety->variety_calendar = $this->calendarService->buildForMonth($variety->id, $year, $month);

        return $variety;
    }

    public function vegetableOptions(): array
    {
        return cache()->remember('vegetable_options', 3600, function () {
            return Vegetable::with('category')
                ->get()
                ->groupBy('category.name')
                ->map(fn ($vegetables) => $vegetables->pluck('name', 'id')->toArray())
                ->toArray();
        });
    }

    public function categoryOptions(): array
    {
        return cache()->remember('category_options', 3600, function () {
            return Category::orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        });
    }

    private function applyPriceFilter(Builder $q, string $filter): void
    {
        match ($filter) {
            'week' => $q->where('recorded_at', '>=', now()->subWeek()),
            'month' => $q->where('recorded_at', '>=', now()->subMonth()),
            'stale' => $q->where('recorded_at', '<', now()->subMonth()),
            default => null,
        };
    }

    private function resolveSupplyMunicipalities(int $varietyId): array
    {
        return DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('farmer_profiles', 'users.id', '=', 'farmer_profiles.user_id')
            ->join('municipalities', 'farmer_profiles.municipality_id', '=', 'municipalities.id')
            ->where('posts.variety_id', $varietyId)
            ->where('posts.type', PostType::Supply->value)
            ->where('posts.status', PostStatus::Ongoing->value)
            ->groupBy('municipalities.id', 'municipalities.name')
            ->select(
                'municipalities.name as municipality_name',
                DB::raw('SUM(posts.quantity_kg) as total_kg'),
            )
            ->get()
            ->map(fn ($row) => [
                'name' => $row->municipality_name,
                'total_kg' => (float) $row->total_kg,
            ])
            ->sortByDesc('total_kg')
            ->values()
            ->toArray();
    }
}
