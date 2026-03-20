<?php

namespace App\Services\Product;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VarietyService
{
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

    public function paginated(int $perPage = 20, ?string $priceFilter = null, ?string $search = null): LengthAwarePaginator
    {
        $query = Variety::with([
            'vegetable.category',
            'latestPrice',
            'media',
        ])->withCount([
            'posts as supply_count' => fn (Builder $q) => $q->supply(),
            'posts as demand_count' => fn (Builder $q) => $q->demand(),
        ]);

        if ($priceFilter) {
            $query->whereHas('latestPrice', function (Builder $q) use ($priceFilter) {
                match ($priceFilter) {
                    'week' => $q->where('recorded_at', '>=', now()->subWeek()),
                    'month' => $q->where('recorded_at', '>=', now()->subMonth()),
                    'stale' => $q->where('recorded_at', '<', now()->subMonth()),
                    default => null,
                };
            });
        }

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('vegetable', fn (Builder $vq) => $vq->where('name', 'like', "%{$search}%"));
            });
        }

        $municipalitySupplies = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('farmer_profiles', 'users.id', '=', 'farmer_profiles.user_id')
            ->join('municipalities', 'farmer_profiles.municipality_id', '=', 'municipalities.id')
            ->whereIn('posts.variety_id', $query->pluck('id'))
            ->where('posts.type', PostType::Supply->value)
            ->where('posts.status', PostStatus::Ongoing->value)
            ->groupBy('posts.variety_id', 'municipalities.id', 'municipalities.name')
            ->select(
                'posts.variety_id',
                'municipalities.name as municipality_name',
                DB::raw('SUM(posts.quantity_kg) as total_kg'),
            )
            ->get()
            ->groupBy('variety_id');

        return $query->orderBy('name')
            ->paginate($perPage)
            ->through(function (Variety $variety) use ($municipalitySupplies) {
                $variety->supply_municipalities = $municipalitySupplies
                    ->get($variety->id, collect())
                    ->map(fn ($row) => [
                        'name' => $row->municipality_name,
                        'total_kg' => (float) $row->total_kg,
                    ])
                    ->sortByDesc('total_kg')
                    ->values()
                    ->toArray();

                return $variety;
            });
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

    public function show(Variety $variety): Variety
    {
        $variety->load(['vegetable.category', 'latestPrice', 'recentPrices', 'media'])
            ->loadCount([
                'posts as supply_count' => fn (Builder $q) => $q->supply()->ongoing(),
                'posts as demand_count' => fn (Builder $q) => $q->demand()->ongoing(),
            ]);

        $variety->supply_municipalities = DB::table('posts')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('farmer_profiles', 'users.id', '=', 'farmer_profiles.user_id')
            ->join('municipalities', 'farmer_profiles.municipality_id', '=', 'municipalities.id')
            ->where('posts.variety_id', $variety->id)
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

        return $variety;
    }

    public function vegetableOptions(): array
    {
        return cache()->remember('vegetable_options', 3600, function () {
            return Vegetable::with('category')
                ->get()
                ->groupBy('category.name')
                ->map(function ($vegetables) {
                    return $vegetables->pluck('name', 'id')->toArray();
                })
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

    public function detailed(Variety $variety): Variety
    {
        return $variety->load(['vegetable.category', 'latestPrice', 'recentPrices', 'media']);
    }
}
