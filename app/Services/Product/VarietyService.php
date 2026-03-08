<?php

namespace App\Services\Product;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VarietyService
{
    public function summary(): array
    {
        $oneWeekAgo  = now()->subWeek();
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
                AVG(varieties.weeks_to_harvest) as avg_weeks,
                SUM(CASE WHEN ph.recorded_at >= ? THEN 1 ELSE 0 END) as updated_week,
                SUM(CASE WHEN ph.recorded_at >= ? AND ph.recorded_at < ? THEN 1 ELSE 0 END) as updated_month,
                SUM(CASE WHEN ph.recorded_at < ? AND ph.recorded_at IS NOT NULL THEN 1 ELSE 0 END) as stale,
                SUM(CASE WHEN ph.id IS NULL THEN 1 ELSE 0 END) as no_price
            ', [$oneWeekAgo, $oneMonthAgo, $oneMonthAgo, $oneMonthAgo])
            ->first();

        return [
            'total_varieties'          => (int) $priceStats->total_varieties,
            'total_vegetables'         => Vegetable::count(),
            'average_weeks_to_harvest' => round($priceStats->avg_weeks ?? 0, 1),
            'price_stats'              => [
                'updated_week'  => (int) $priceStats->updated_week,
                'updated_month' => (int) $priceStats->updated_month,
                'stale'         => (int) $priceStats->stale,
                'no_price'      => (int) $priceStats->no_price,
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
            'posts as supply_count' => fn (Builder $q) => $q
                ->where('postable_type', FarmerSupply::class)
                ->ongoing(),
            'posts as demand_count' => fn (Builder $q) => $q
                ->where('postable_type', DealerDemand::class)
                ->ongoing(),
        ]);

        if ($priceFilter) {
            $query->whereHas('latestPrice', function (Builder $q) use ($priceFilter) {
                match ($priceFilter) {
                    'week'  => $q->where('recorded_at', '>=', now()->subWeek()),
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
            ->join('farmer_supplies', function ($join) {
                $join->on('posts.postable_id', '=', 'farmer_supplies.id')
                    ->where('posts.postable_type', FarmerSupply::class);
            })
            ->join('farmer_profiles', 'farmer_supplies.farmer_id', '=', 'farmer_profiles.id')
            ->join('municipalities', 'farmer_profiles.municipality_id', '=', 'municipalities.id')
            ->whereIn('posts.variety_id', $query->pluck('id'))
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
            ->through(function ($variety) use ($municipalitySupplies) {
                $variety->price_updated_human = $variety->latestPrice->recorded_at->diffForHumans();
                $variety->price_updated_date  = $variety->latestPrice->recorded_at->format('M d, Y');

                $daysOld = $variety->latestPrice->recorded_at->diffInDays(now());
                $variety->price_freshness = match (true) {
                    $daysOld <= 7   => 'recent',
                    $daysOld <= 30  => 'stable',
                    $daysOld <= 90  => 'very stable',
                    default         => 'stale',
                };

                $variety->supply_municipalities = $municipalitySupplies
                    ->get($variety->id, collect())
                    ->map(fn ($row) => [
                        'name'     => $row->municipality_name,
                        'total_kg' => (float) $row->total_kg,
                    ])
                    ->sortByDesc('total_kg')
                    ->values()
                    ->toArray();

                return $variety;
            });
    }

    /* Admin Options for creating variety */
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

    public function detailed(Variety $variety): array
    {
        $variety->load(['vegetable.category', 'latestPrice', 'recentPrices', 'media']);

        return [
            'id'               => $variety->id,
            'name'             => $variety->name,
            'image_url'        => $variety->getFirstMediaUrl('variety_image'),
            'weeks_to_harvest' => $variety->weeks_to_harvest,
            'vegetable'        => [
                'id'       => $variety->vegetable->id,
                'name'     => $variety->vegetable->name,
                'category' => [
                    'id'   => $variety->vegetable->category->id,
                    'name' => $variety->vegetable->category->name,
                ],
            ],
            'latest_price' => $variety->latestPrice
                ? [
                    'price_min'   => (float) $variety->latestPrice->price_min,
                    'price_max'   => (float) $variety->latestPrice->price_max,
                    'recorded_at' => $variety->latestPrice->recorded_at->format('M d, Y'),
                    'freshness'   => self::computePriceFreshness($variety->latestPrice->recorded_at),
                ] : null,
            'recent_prices' => $variety->recentPrices
                ->sortBy('recorded_at')
                ->map(fn ($p) => [
                    'price_min' => (float) $p->price_min,
                    'price_max' => (float) $p->price_max,
                    'recorded_at'=> $p->recorded_at->format('M d, Y'),
                ])
                ->values(),
        ];
    }

    public static function forCatalog(int $perPage = 20, ?string $search = null, ?int $categoryId = null): LengthAwarePaginator
    {
        return Variety::with(['vegetable.category', 'latestPrice', 'media'])
            ->when($search, fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            ->when($categoryId, fn (Builder $q) => $q->whereHas('vegetable', fn ($q) => $q->where('category_id', $categoryId)))
            ->orderBy('name')
            ->paginate($perPage)
            ->through(fn ($variety) => [
                'id'        => $variety->id,
                'name'      => $variety->name,
                'image_url' => $variety->getFirstMediaUrl('variety_image'),
                'vegetable' => [
                    'name'     => $variety->vegetable->name,
                    'category' => $variety->vegetable->category->name,
                ],
                'weeks_to_harvest' => $variety->weeks_to_harvest,
                'latest_price'     => $variety->latestPrice
                    ? [
                        'price_min' => (float) $variety->latestPrice->price_min,
                        'price_max' => (float) $variety->latestPrice->price_max,
                    ]
                    : null,
            ]);
    }

    private static function computePriceFreshness(CarbonInterface $date): string
    {
        $daysOld = $date->diffInDays(now());

        return match (true) {
            $daysOld <= 7 => 'recent',
            $daysOld <= 30 => 'stable',
            $daysOld <= 90 => 'very stable',
            default => 'stale',
        };
    }
}
