<?php

namespace App\Services\Dealer;

use App\Models\Product\Category;
use App\Models\Product\Planting;
use App\Models\Product\Variety;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DealerMarketService
{
    /**
     * Get paginated active plantings with filters
     */
    public static function paginated(
        int $perPage = 20,
        ?string $searchQuery = null,
        ?int $categoryId = null
    ): LengthAwarePaginator {
        $query = Planting::with([
            'variety.vegetable.category',
            'farmer.user',
            'farmer.province',
            'farmer.municipality',
            'farmer.barangay',
        ])
            ->where('status', 'active')
            ->whereHas('farmer', fn(Builder $q) => $q->where('is_approved', true));

        // Search filter (variety name or vegetable name)
        if ($searchQuery) {
            $query->whereHas('variety', function (Builder $q) use ($searchQuery) {
                $q->where('name', 'LIKE', "%{$searchQuery}%")
                    ->orWhereHas('vegetable', fn(Builder $vq) => 
                        $vq->where('name', 'LIKE', "%{$searchQuery}%")
                    );
            });
        }

        // Category filter
        if ($categoryId) {
            $query->whereHas('variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $categoryId)
            );
        }

        return $query
            ->orderBy('expected_harvest_date', 'asc')
            ->paginate($perPage)
            ->through(function ($planting) {
                $daysUntilHarvest = $planting->days_unill_harvest;
                
                // Determine urgency color
                $urgency = 'normal';
                if ($daysUntilHarvest !== null) {
                    if ($daysUntilHarvest <= 0) {
                        $urgency = 'overdue';
                    } elseif ($daysUntilHarvest <= 6) {
                        $urgency = 'soon';
                    }
                }

                return [
                    'id' => $planting->id,
                    'variety' => [
                        'id' => $planting->variety->id,
                        'name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                        'image_path' => $planting->variety->image_path,
                        'category' => [
                            'id' => $planting->variety->vegetable->category->id,
                            'name' => $planting->variety->vegetable->category->name,
                        ],
                    ],
                    'farmer' => [
                        'id' => $planting->farmer->id,
                        'name' => $planting->farmer->user->name,
                        'location' => [
                            'barangay' => $planting->farmer->barangay->name,
                            'municipality' => $planting->farmer->municipality->name,
                            'province' => $planting->farmer->province->name,
                            'full' => "{$planting->farmer->barangay->name}, {$planting->farmer->municipality->name}",
                        ],
                    ],
                    'weight_kg' => (float) $planting->weight_kg,
                    'harvest_date' => $planting->expected_harvest_date->format('M d, Y'),
                    'harvest_date_human' => $planting->expected_harvest_date->diffForHumans(),
                    'days_until_harvest' => $daysUntilHarvest,
                    'urgency' => $urgency,
                ];
            });
    }

    /**
     * Get market insights data
     */
    public static function insights(): array
    {
        return [
            'trending' => self::getTrendingVarieties(),
            'supply_gaps' => self::getSupplyGaps(),
            'harvest_forecast' => self::getHarvestForecast(),
            'stats' => self::getQuickStats(),
        ];
    }

    /**
     * Get category filter options
     */
    public static function categoryOptions(): array
    {
        return Category::orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->toArray();
    }

    /**
     * Get trending varieties (most active plantings in last 30 days)
     */
    private static function getTrendingVarieties(): array
    {
        $current = Planting::selectRaw('
                variety_id,
                COUNT(*) as current_count
            ')
            ->where('status', 'active')
            ->where('date_planted', '>=', now()->subDays(30))
            ->whereHas('farmer', fn(Builder $q) => $q->where('is_approved', true))
            ->groupBy('variety_id')
            ->orderByDesc('current_count')
            ->limit(5)
            ->get();

        $previous = Planting::selectRaw('
                variety_id,
                COUNT(*) as previous_count
            ')
            ->where('status', 'active')
            ->whereBetween('date_planted', [now()->subDays(60), now()->subDays(30)])
            ->whereHas('farmer', fn(Builder $q) => $q->where('is_approved', true))
            ->groupBy('variety_id')
            ->get()
            ->keyBy('variety_id');

        return $current->map(function ($item) use ($previous) {
            $variety = Variety::with('vegetable')->find($item->variety_id);
            $prevCount = $previous->get($item->variety_id)?->previous_count ?? 0;
            
            $change = 0;
            if ($prevCount > 0) {
                $change = round((($item->current_count - $prevCount) / $prevCount) * 100, 1);
            } elseif ($item->current_count > 0) {
                $change = 100;
            }

            return [
                'variety_id' => $variety->id,
                'name' => $variety->vegetable->name . ' ' . $variety->name,
                'count' => (int) $item->current_count,
                'change_percent' => $change,
                'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'neutral'),
            ];
        })->toArray();
    }

    /**
     * Get supply gaps (categories with low active plantings)
     */
    private static function getSupplyGaps(): array
    {
        $threshold = 15; // Categories with fewer than 15 active plantings

        return DB::table('plantings')
            ->join('varieties', 'plantings.variety_id', '=', 'varieties.id')
            ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
            ->join('categories', 'vegetables.category_id', '=', 'categories.id')
            ->join('farmer_profiles', 'plantings.farmer_id', '=', 'farmer_profiles.id')
            ->where('plantings.status', 'active')
            ->where('farmer_profiles.is_approved', true)
            ->select('categories.id', 'categories.name', DB::raw('COUNT(plantings.id) as active_count'))
            ->groupBy('categories.id', 'categories.name')
            ->having('active_count', '<', $threshold)
            ->orderBy('active_count', 'asc')
            ->get()
            ->map(fn($gap) => [
                'category_id' => $gap->id,
                'category_name' => $gap->name,
                'active_count' => (int) $gap->active_count,
            ])
            ->toArray();
    }

    /**
     * Get 4-week harvest forecast by category
     */
    private static function getHarvestForecast(): array
    {
        $forecast = [];

        for ($week = 0; $week < 4; $week++) {
            $startDate = now()->addWeeks($week)->startOfWeek();
            $endDate = now()->addWeeks($week)->endOfWeek();

            $data = Planting::query()
                ->join('varieties', 'plantings.variety_id', '=', 'varieties.id')
                ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
                ->join('categories', 'vegetables.category_id', '=', 'categories.id')
                ->join('farmer_profiles', 'plantings.farmer_id', '=', 'farmer_profiles.id')
                ->whereBetween('plantings.expected_harvest_date', [$startDate, $endDate])
                ->where('plantings.status', 'active')
                ->where('farmer_profiles.is_approved', true)
                ->selectRaw('categories.name as category, SUM(CAST(plantings.weight_kg AS DECIMAL(10,2))) as total_weight')
                ->groupBy('categories.id', 'categories.name')
                ->get();

            $weekData = [
                'week' => 'Week ' . ($week + 1),
                'date_range' => $startDate->format('M d') . ' - ' . $endDate->format('M d'),
                'total_weight' => 0,
            ];

            foreach ($data as $row) {
                $weekData[$row->category] = (float) $row->total_weight;
                $weekData['total_weight'] += (float) $row->total_weight;
            }

            $forecast[] = $weekData;
        }

        return $forecast;
    }

    /**
     * Get quick stats for dashboard
     */
    private static function getQuickStats(): array
    {
        $totalActive = Planting::where('status', 'active')
            ->whereHas('farmer', fn(Builder $q) => $q->where('is_approved', true))
            ->count();

        $harvestingThisWeek = Planting::where('status', 'active')
            ->whereHas('farmer', fn(Builder $q) => $q->where('is_approved', true))
            ->whereBetween('expected_harvest_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $newToday = Planting::where('status', 'active')
            ->whereHas('farmer', fn(Builder $q) => $q->where('is_approved', true))
            ->whereDate('created_at', today())
            ->count();

        return [
            'total_active_plantings' => $totalActive,
            'harvesting_this_week' => $harvestingThisWeek,
            'new_listings_today' => $newToday,
        ];
    }
}
