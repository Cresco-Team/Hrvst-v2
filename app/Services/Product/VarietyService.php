<?php

namespace App\Services\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class VarietyService
{
    public static function paginated(int $perPage = 20, ?string $priceFilter = null): LengthAwarePaginator
    {
        $query = Variety::with([
            'vegetable.category',
            'latestPrice',
            'media',
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

        return $query->orderBy('name')
            ->paginate($perPage)
            ->through(function ($variety) {
                $variety->price_updated_human = $variety->latestPrice->recorded_at->diffForHumans();
                $variety->price_updated_date  = $variety->latestPrice->recorded_at->format('M d, Y');

                $daysOld = $variety->latestPrice->recorded_at->diffInDays(now());
                $variety->price_freshness = match (true) {
                    $daysOld <= 7   => 'recent',
                    $daysOld <= 30  => 'stable',
                    $daysOld <= 90  => 'very stable',
                    default         => 'stale',
                };

                return $variety;
            });
    }

    public static function detailed(Variety $variety): array
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
                ]
                : null,
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

    public static function summary(): array
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

    public static function vegetableOptions(): array
    {
        return cache()->remember('vegetable_options', 3600, function () {
            return Vegetable::with('category')
                ->get()
                ->groupBy('category.name')
                ->map(fn ($vegetables) => $vegetables->pluck('name', 'id')->toArray())
                ->toArray();
        });
    }

    public function create(array $validated, ?UploadedFile $image = null): Variety
    {
        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety = Variety::create($validated);

        if ($image !== null) {
            $variety->addMedia($image)->toMediaCollection('variety_image');
        }

        $this->createPriceHistory($variety, $priceMin, $priceMax);

        return $variety->load('latestPrice');
    }

    public function update(Variety $variety, array $validated, ?UploadedFile $image = null): Variety
    {
        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety->update($validated);

        if ($image !== null) {
            $variety->addMedia($image)->toMediaCollection('variety_image');
        }

        $this->updatePriceHistory($variety, $priceMin, $priceMax);

        return $variety->load('latestPrice');
    }

    public function delete(Variety $variety): bool
    {
        // InteractsWithMedia hooks into the model 'deleted' event and removes
        // all media files and records — no manual cleanup needed.
        // NOTE: This always returns true. The controller branch for false is dead code
        // and should be addressed in a separate PR (add a guard for active posts).
        $variety->delete();

        return true;
    }

    private function createPriceHistory(Variety $variety, float $priceMin, float $priceMax): void
    {
        $variety->prices()->create([
            'price_min'   => $priceMin,
            'price_max'   => $priceMax,
            'recorded_at' => now(),
        ]);
    }

    private function updatePriceHistory(Variety $variety, float $priceMin, float $priceMax): void
    {
        $variety->prices()->create([
            'price_min'   => $priceMin,
            'price_max'   => $priceMax,
            'recorded_at' => now(),
        ]);
    }
}
