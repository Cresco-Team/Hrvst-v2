<?php

namespace App\Services\Product;

use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VarietyService
{
    public static function paginated(int $perPage = 20): LengthAwarePaginator
    {
        return Variety::with([
            'vegetable.category',
            'latestPrice'
        ])
            ->orderBy('name')
            ->paginate($perPage)
            ->through(function ($variety) {
                if ($variety->latestPrice) {
                    $variety->price_updated_human = $variety->latestPrice->recorded_at->diffForHumans();
                    $variety->price_updated_date = $variety->latestPrice->recorded_at->format('M d, Y');
                }
                return $variety;
            });
    }

    public static function summary(): array
    {
        return [
            'total_varieties' => Variety::count(),
            'total_vegetables' => Vegetable::count(),
            'average_weeks_to_harvest' => round(Variety::avg('weeks_to_harvest'), 1),
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