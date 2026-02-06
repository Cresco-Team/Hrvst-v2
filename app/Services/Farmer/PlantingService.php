<?php

namespace App\Services\Farmer;

use App\Models\Product\Planting;
use App\Models\Product\Variety;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlantingService
{
    /**
     * Get paginated active plantings (cards view).
     */
    public static function paginatedActive(
        int $farmerId,
        ?string $searchQuery = null,
        int $perPage = 20,
        int $page = 1
    ): LengthAwarePaginator {
        $query = Planting::with([
            'variety.vegetable.category',
        ])
            ->where('farmer_id', $farmerId)
            ->where('status', 'active');

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
                    'status_badge' => $planting->status_badge,
                    'can_edit' => true,
                    'can_delete' => !$planting->conversations()->exists(),
                    'can_harvest' => true,
                    'can_cancel' => true,
                ];
            });
    }

    /**
     * Get paginated archived plantings (table view).
     */
    public static function paginatedArchived(
        int $farmerId,
        ?string $searchQuery = null,
        int $perPage = 20,
        int $page = 1
    ): LengthAwarePaginator {
        $query = Planting::with([
            'variety.vegetable.category',
        ])
            ->where('farmer_id', $farmerId)
            ->whereIn('status', ['harvested', 'expired', 'cancelled']);

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
            ->latest('updated_at')
            ->paginate($perPage, ['*'], 'page', $page)
            ->through(function ($planting) {
                return [
                    'id' => $planting->id,
                    'variety_name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                    'category' => $planting->variety->vegetable->category->name,
                    'weight_kg' => $planting->weight_kg,
                    'date_planted' => $planting->date_planted->format('M d, Y'),
                    'expected_harvest_date' => $planting->expected_harvest_date->format('M d, Y'),
                    'date_completed' => $planting->date_harvested?->format('M d, Y') ?? $planting->updated_at->format('M d, Y'),
                    'status' => $planting->status,
                ];
            });
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
