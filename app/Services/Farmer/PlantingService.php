<?php

namespace App\Services\Farmer;

use App\Models\Product\Planting;
use App\Models\Product\Variety;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlantingService
{
    public static function paginated(
        int $farmerId, 
        int $perPage = 20,
        ?string $statusFilter = null,
        ?string $searchQuery = null,
        int $page = 1
    ): LengthAwarePaginator {
        $query = Planting::with([
            'variety.vegetable.category',
        ])
            ->where('farmer_id', $farmerId);

        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'harvesting_soon') {
                $query->harvestingSoon();
            } else {
                $query->where('status', $statusFilter);
            }
        }

        if ($searchQuery) {
            $query->where(function ($q) use ($searchQuery) {
                $q->whereHas('variety.vegetable', function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%");
                })
                ->orWhereHas('variety', function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%");
                })
                ->orWhereHas('variety.vegetable.category', function ($q) use ($searchQuery) {
                    $q->where('name', 'like', "%{$searchQuery}%");
                });
            });
        }

        return $query
            ->orderBy('expected_harvest_date', 'asc')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($planting) {
                return [
                    'id' => $planting->id,
                    'variety' => [
                        'id' => $planting->variety?->id ?? 0,
                        'name' => ($planting->variety?->vegetable?->name ?? 'Unknown') . ' ' . ($planting->variety?->name ?? ''),
                        'category' => $planting->variety?->vegetable?->category?->name ?? 'Unknown',
                        'image_path' => $planting->variety?->image_url ?? '',
                    ],
                    'weight_kg' => (float) $planting->weight_kg,
                    'date_planted' => $planting->date_planted?->format('M d, Y') ?? 'Unknown',
                    'date_planted_human' => $planting->date_planted?->diffForHumans() ?? 'Unknown',
                    'expected_harvest_date' => $planting->expected_harvest_date?->format('M d, Y') ?? 'Unknown',
                    'days_until_harvest' => $planting->days_unill_harvest,
                    'status' => $planting->status ?? 'active',
                    'status_badge' => $planting->status_badge ?? 'Unknown',
                    'can_edit' => $planting->status === 'active',
                    'can_delete' => !$planting->conversations()->exists(),
                    'can_harvest' => $planting->status === 'active',
                    'can_cancel' => $planting->status === 'active',
                ];
            });
    }

    public static function summary(int $farmerId): array
    {
        $today = now()->startOfDay();
        $sevenDaysFromNow = now()->addDays(7)->endOfDay();
        $startOfMonth = now()->startOfMonth();

        $totalActive = Planting::where('farmer_id', $farmerId)
            ->where('status', 'active')
            ->count();

        $totalWeightActive = Planting::where('farmer_id', $farmerId)
            ->where('status', 'active')
            ->sum('weight_kg');

        $harvestingSoon = Planting::where('farmer_id', $farmerId)
            ->where('status', 'active')
            ->whereBetween('expected_harvest_date', [$today, $sevenDaysFromNow])
            ->count();

        $harvestedThisMonth = Planting::where('farmer_id', $farmerId)
            ->where('status', 'harvested')
            ->where('date_harvested', '>=', $startOfMonth)
            ->count();

        return [
            'total_active' => $totalActive,
            'total_weight_active' => round($totalWeightActive ?? 0, 2),
            'harvesting_soon' => $harvestingSoon,
            'harvested_this_month' => $harvestedThisMonth,
        ];
    }

    public static function varietyOptionsForForm(): array
    {
        return Variety::with('vegetable.category')
            ->orderBy('name')
            ->get()
            ->groupBy('vegetable.category.name')
            ->map(fn($varieties) => $varieties->map(fn($variety) => [
                'id' => $variety->id,
                'name' => $variety->vegetable->name . ' ' . $variety->name,
                'weeks_to_harvest' => $variety->weeks_to_harvest,
            ])->values()->toArray())
            ->toArray();
    }

    public function create(int $farmerId, array $validated): Planting
    {
        return Planting::create([
            'farmer_id' => $farmerId,
            ...$validated,
        ]);
    }

    public function update(Planting $planting, array $validated): Planting
    {
        if ($planting->status !== 'active') {
            throw new \LogicException('Only active plantings can be updated.');
        }

        if (isset($validated['expected_harvest_date'])) {
            $harvestDate = Carbon::parse($validated['expected_harvest_date']);
            
            if ($harvestDate->isPast()) {
                $validated['status'] = 'expired';
            }
        }

        $planting->update($validated);
        
        return $planting->fresh();
    }

    public function markAsHarvested(Planting $planting, ?float $actualWeight = null): void
    {
        if ($planting->status !== 'active') {
            throw new \LogicException('Only active plantings can be harvested.');
        }

        $planting->markAsHarvested($actualWeight);
    }

    public function markAsCancelled(Planting $planting): void
    {
        if ($planting->status !== 'active') {
            throw new \LogicException('Only active plantings can be cancelled.');
        }

        $planting->update(['status' => 'cancelled']);
    }

    public function delete(Planting $planting): bool
    {
        if ($planting->conversations()->exists()) {
            return false;
        }

        $planting->delete();
        
        return true;
    }
}
