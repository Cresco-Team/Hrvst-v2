<?php

namespace App\Services\Product;

use App\Models\Product\Category;
use App\Models\Product\PriceHistory;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use App\Services\Media\ImageUploadService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class VarietyService
{
    public function __construct(
        private ImageUploadService $imageService
    ) {}

    public static function paginated(int $perPage = 20, ?string $priceFilter = null): LengthAwarePaginator
    {
        $query = Variety::with([
            'vegetable.category',
            'latestPrice',
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

        return $query->orderBy('name')
            ->paginate($perPage)
            ->through(function ($variety) {
                $variety->price_updated_human = $variety->latestPrice->recorded_at->diffForHumans();
                $variety->price_updated_date = $variety->latestPrice->recorded_at->format('M d, Y');

                $daysOld = $variety->latestPrice->recorded_at->diffInDays(now());
                $variety->price_freshness = match (true) {
                    $daysOld <= 7 => 'recent',
                    $daysOld <= 30 => 'stable',
                    $daysOld <= 90 => 'very stable',
                    default => 'stale',
                };

                return $variety;
            });
    }

    public static function detailed(Variety $variety): array
    {
        $variety->load(['vegetable.category', 'latestPrice', 'recentPrices']);

        return [
            'id'               => $variety->id,
            'name'             => $variety->name,
            'image_url'        => $variety->image_url,
            'weeks_to_harvest' => $variety->weeks_to_harvest,
            'vegetable'        => [
                'id'       => $variety->vegetable->id,
                'name'     => $variety->vegetable->name,
                'category' => [
                    'id'   => $variety->vegetable->category->id,
                    'name' => $variety->vegetable->category->name,
                ],
            ],
            'latest_price'  => $variety->latestPrice ? [
                'price_min'  => (float) $variety->latestPrice->price_min,
                'price_max'  => (float) $variety->latestPrice->price_max,
                'recorded_at'=> $variety->latestPrice->recorded_at->format('M d, Y'),
                'freshness'  => self::computePriceFreshness($variety->latestPrice->recorded_at),
            ] : null,
            'recent_prices' => $variety->recentPrices
                ->sortBy('recorded_at')
                ->map(fn ($p) => [
                    'price_min'   => (float) $p->price_min,
                    'price_max'   => (float) $p->price_max,
                    'recorded_at' => $p->recorded_at->format('M d, Y'),
                ])
                ->values(),
            ];
    }

    public static function forCatalog(int $perPage = 20, ?string $search = null, ?int $categoryId = null): LengthAwarePaginator
    {
        return Variety::with([
            'vegetable.category',
            'latestPrice',
            'recentPrices',
        ])
            ->when($search, fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('vegetable', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            )
            ->when($categoryId, fn (Builder $q) => $q
                ->whereHas('vegetable.category', fn (Builder $q) => $q->where('id', $categoryId))
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->through(function ($variety) {
                return [
                    'id' => $variety->id,
                    'name' => $variety->name,
                    'image_url' => $variety->image_url,
                    'weeks_to_harvest' => $variety->weeks_to_harvest,
                    'vegetable' => [
                        'id' => $variety->vegetable->id,
                        'name' => $variety->vegetable->name,
                        'category' => [
                            'id' => $variety->vegetable->category->id,
                            'name' => $variety->vegetable->category->name,
                        ],
                    ],
                    'latest_price' => $variety->latestPrice ? [
                        'price_min' => (float) $variety->latestPrice->price_min,
                        'price_max' => (float) $variety->latestPrice->price_max,
                        'recorded_at' => $variety->latestPrice->recorded_at->format('M d, Y'),
                        'freshness' => self::computePriceFreshness($variety->latestPrice->recorded_at),
                    ] : null,
                    'recent_prices' => $variety->recentPrices
                        ->sortBy('recorded_at')
                        ->map(fn ($p) => [
                            'price_min' => (float) $p->price_min,
                            'price_max' => (float) $p->price_max,
                            'recorded_at' => $p->recorded_at->format('M d, Y'),
                        ])
                        ->values(),
                ];
            });
    }

    public static function categoryOptions(): array
    {
        return Category::orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public static function summary(): array
    {
        $now = now();
        $oneWeekAgo = $now->copy()->subWeek();
        $oneMonthAgo = $now->copy()->subMonth();

        $priceStats = DB::table('varieties as v')
            ->leftJoin('price_histories as ph', function ($join) {
                $join->on('v.id', '=', 'ph.variety_id')
                    ->whereRaw('ph.id = (SELECT id FROM price_histories WHERE variety_id = v.id ORDER BY recorded_at DESC LIMIT 1)');
            })
            ->selectRaw('
                COUNT(v.id) as total_varieties,
                AVG(v.weeks_to_harvest) as avg_weeks,
                SUM(CASE WHEN ph.recorded_at >= ? THEN 1 ELSE 0 END) as updated_week,
                SUM(CASE WHEN ph.recorded_at >= ? THEN 1 ELSE 0 END) as updated_month,
                SUM(CASE WHEN ph.recorded_at < ? AND ph.recorded_at IS NOT NULL THEN 1 ELSE 0 END) as stale,
                SUM(CASE WHEN ph.id IS NULL THEN 1 ELSE 0 END) as no_price
            ', [$oneWeekAgo, $oneMonthAgo, $oneMonthAgo])
            ->first();

        return [
            'total_varieties' => (int) $priceStats->total_varieties,
            'total_vegetables' => Vegetable::count(),
            'average_weeks_to_harvest' => round($priceStats->avg_weeks ?? 0, 1),
            'price_stats' => [
                'updated_week' => (int) $priceStats->updated_week,
                'updated_month' => (int) $priceStats->updated_month,
                'stale' => (int) $priceStats->stale,
                'no_price' => (int) $priceStats->no_price,
            ],
        ];
    }

    public static function vegetableOptions(): array
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

    public function create(array $validated, ?UploadedFile $image = null): Variety
    {
        if ($image) {
            $validated['image_path'] = $this->imageService->uploadVarietyImage($image);
        }

        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety = Variety::create($validated);

        $this->createPriceHistory($variety, $priceMin, $priceMax);

        return $variety->load('latestPrice');
    }

    public function update(Variety $variety, array $validated, ?UploadedFile $image = null): Variety
    {
        if ($image) {
            $validated['image_path'] = $this->imageService->uploadVarietyImage(
                $image,
                $variety->image_path
            );
        }

        $priceMin = $validated['price_min'];
        $priceMax = $validated['price_max'];
        unset($validated['price_min'], $validated['price_max']);

        $variety->update($validated);

        $this->updatePriceHistory($variety, $priceMin, $priceMax);

        return $variety->load('latestPrice');
    }

    public function delete(Variety $variety): bool
    {
        if ($variety->image_path) {
            $this->imageService->deleteVarietyImage($variety->image_path);
        }

        $variety->delete();

        return true;
    }

    private function createPriceHistory(Variety $variety, float $priceMin, float $priceMax): void
    {
        PriceHistory::create([
            'variety_id' => $variety->id,
            'price_min' => $priceMin,
            'price_max' => $priceMax,
            'recorded_at' => now()->startOfDay(),
        ]);
    }

    private function updatePriceHistory(Variety $variety, float $priceMin, float $priceMax): void
    {
        PriceHistory::updateOrCreate(
            [
                'variety_id' => $variety->id,
                'recorded_at' => now()->startOfDay(),
            ],
            [
                'price_min' => $priceMin,
                'price_max' => $priceMax,
            ]
        );
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
