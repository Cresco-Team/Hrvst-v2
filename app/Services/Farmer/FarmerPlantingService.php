<?php

namespace App\Services\Farmer;

use App\Models\Product\Planting;
use App\Models\Product\Variety;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FarmerPlantingService
{
    /**
     * Get paginated plantings for farmer with filters.
     */
    public static function paginated(
        int $farmerId, 
        int $perPage = 20,
        ?string $statusFilter = null
    ): LengthAwarePaginator {
        $query = Planting::with([
            'variety.vegetable.category',
        ])
            ->where('farmer_id', $farmerId);

        // Apply status filter
        if ($statusFilter && $statusFilter !== 'all') {
            if ($statusFilter === 'harvesting_soon') {
                $query->harvestingSoon();
            } else {
                $query->where('status', $statusFilter);
            }
        }

        return $query
            ->orderBy('expected_harvest_date', 'asc')
            ->paginate($perPage)
            ->through(function ($planting) {
                return [
                    'id' => $planting->id,
                    'variety' => [
                        'id' => $planting->variety->id,
                        'name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                        'category' => $planting->variety->vegetable->category->name,
                        'image_path' => $planting->variety->image_path,
                    ],
                    'weight_kg' => $planting->weight_kg,
                    'date_planted' => $planting->date_planted->format('M d, Y'),
                    'date_planted_human' => $planting->date_planted->diffForHumans(),
                    'expected_harvest_date' => $planting->expected_harvest_date->format('M d, Y'),
                    'days_until_harvest' => $planting->days_unill_harvest,
                    'status' => $planting->status,
                    'status_badge' => $planting->status_badge,
                    'can_edit' => $planting->status === 'active',
                    'can_delete' => !$planting->conversations()->exists(),
                    'can_harvest' => $planting->status === 'active',
                    'can_cancel' => $planting->status === 'active',
                ];
            });
    }

    /**
     * Get summary statistics (optimized single query).
     */
    public static function summary(int $farmerId): array
    {
        $stats = DB::table('plantings')
            ->where('farmer_id', $farmerId)
            ->selectRaw('
                COUNT(CASE WHEN status = "active" THEN 1 END) as total_active,
                SUM(CASE WHEN status = "active" THEN weight_kg ELSE 0 END) as total_weight_active,
                COUNT(CASE WHEN status = "active" 
                    AND expected_harvest_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) 
                    THEN 1 END) as harvesting_soon,
                COUNT(CASE WHEN status = "harvested" 
                    AND date_harvested >= ? THEN 1 END) as harvested_this_month
            ', [now()->startOfMonth()])
            ->first();

        return [
            'total_active' => (int) ($stats->total_active ?? 0),
            'total_weight_active' => round($stats->total_weight_active ?? 0, 2),
            'harvesting_soon' => (int) ($stats->harvesting_soon ?? 0),
            'harvested_this_month' => (int) ($stats->harvested_this_month ?? 0),
        ];
    }

    /**
     * Get variety options grouped by category for form.
     */
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

    /**
     * Create a new planting.
     */
    public function create(int $farmerId, array $validated): Planting
    {
        return Planting::create([
            'farmer_id' => $farmerId,
            ...$validated,
        ]);
    }

    /**
     * Update an existing planting (active only).
     */
    public function update(Planting $planting, array $validated): Planting
    {
        if ($planting->status !== 'active') {
            throw new \LogicException('Only active plantings can be updated.');
        }

        $planting->update($validated);
        
        return $planting->fresh();
    }

    /**
     * Mark planting as harvested.
     */
    public function markAsHarvested(Planting $planting, ?float $actualWeight = null): void
    {
        if ($planting->status !== 'active') {
            throw new \LogicException('Only active plantings can be harvested.');
        }

        $planting->markAsHarvested($actualWeight);
    }

    /**
     * Mark planting as cancelled.
     */
    public function markAsCancelled(Planting $planting): void
    {
        if ($planting->status !== 'active') {
            throw new \LogicException('Only active plantings can be cancelled.');
        }

        $planting->update(['status' => 'cancelled']);
    }

    /**
     * Delete a planting (only if no conversations).
     */
    public function delete(Planting $planting): bool
    {
        if ($planting->conversations()->exists()) {
            return false;
        }

        $planting->delete();
        
        return true;
    }
}
