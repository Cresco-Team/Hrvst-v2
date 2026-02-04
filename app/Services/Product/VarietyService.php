<?php

namespace App\Services\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class VarietyService
{
    public static function paginated(int $perPage = 20, ?string $priceFilter = null): LengthAwarePaginator
    {
        $query = Variety::with([
            'vegetable.category',
            'latestPrice'
        ]);

        // Apply price freshness filter
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
                if ($variety->latestPrice) {
                    $variety->price_updated_human = $variety->latestPrice->recorded_at->diffForHumans();
                    $variety->price_updated_date = $variety->latestPrice->recorded_at->format('M d, Y');
                    
                    // Balanced freshness indicator - encourages weekly updates
                    $daysOld = $variety->latestPrice->recorded_at->diffInDays(now());
                    $variety->price_freshness = match (true) {
                        $daysOld <= 3 => 'fresh',      // 0-3 days: Fresh
                        $daysOld <= 7 => 'recent',     // 4-7 days: Still good
                        $daysOld <= 14 => 'okay',      // 8-14 days: Acceptable
                        $daysOld <= 30 => 'aging',     // 15-30 days: Getting old
                        default => 'stale',            // 30+ days: Needs update
                    };
                }
                return $variety;
            });
    }

    public static function summary(): array
    {
        $now = now();
        $oneWeekAgo = $now->copy()->subWeek();
        $oneMonthAgo = $now->copy()->subMonth();

        return [
            'total_varieties' => Variety::count(),
            'total_vegetables' => Vegetable::count(),
            'average_weeks_to_harvest' => round(Variety::avg('weeks_to_harvest'), 1),
            'price_stats' => [
                'updated_week' => Variety::whereHas('latestPrice', fn($q) => 
                    $q->where('recorded_at', '>=', $oneWeekAgo)
                )->count(),
                'updated_month' => Variety::whereHas('latestPrice', fn($q) => 
                    $q->where('recorded_at', '>=', $oneMonthAgo)
                )->count(),
                'stale' => Variety::whereHas('latestPrice', fn($q) => 
                    $q->where('recorded_at', '<', $oneMonthAgo)
                )->count(),
                'no_price' => Variety::doesntHave('latestPrice')->count(),
            ],
        ];
    }

    public static function vegetableOptions(): array
    {
        return Vegetable::with('category')
            ->get()
            ->groupBy('category.name')
            ->map(function ($vegetables) {
                return $vegetables->pluck('name', 'id')->toArray();
            })
            ->toArray();
    }

    public static function create(array $validated): Variety
    {
        return Variety::create($validated);
    }

    public static function update(Variety $variety, array $validated): Variety
    {
        $variety->update($validated);

        return $variety;
    }

    public static function delete(Variety $variety): bool
    {
        if ($variety->plantings()->exists()) {
            return false;
        }

        $variety->delete();

        return true;
    }
}
