<?php

namespace App\Services\Farmer;

use App\Models\Product\Planting;
use App\Models\Product\Variety;
use App\PlantingStatus;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PlantingService
{
    public static function paginated(?int $farmerId = null, ?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Planting::with([
            'farmer.user',
            'farmer.municipality',
            'variety.vegetable.category',
        ]);

        if ($farmerId) {
            $query->where('farmer_id', $farmerId);
        }

        if ($status && $status !== 'all') {
            match($status) {
                'available' => $query->available(),
                'archived' => $query->archived(),
                default => $query->where('status', $status),
            };
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function ($planting) {
                return [
                    'id' => $planting->id,
                    'variety' => [
                        'name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                        'category' => $planting->variety->vegetable->category->name,
                        'image_url' => $planting->variety->image_url,
                    ],
                    'image_url' => $planting->image_url,
                    'weight_kg' => (float) $planting->weight_kg,
                    'asking_price' => (float) $planting->asking_price,
                    'expiration_date' => $planting->expiration_date->format('M d, Y'),
                    'days_until_expiration' => $planting->days_until_expiration,
                    'status' => $planting->status,
                    'created_at_human' => $planting->created_at->diffForHumans(),
                    'status_badge' => $planting->status_badge ?? 'Unknown',
                    'can_edit' => $planting->status === PlantingStatus::Available,
                    'can_delete' => $planting->status === PlantingStatus::Archived,
                ];
            });
    }

    public static function summary(int $farmerId): array
    {
        $today = now()->startOfDay();
        $sevenDaysFromNow = now()->addDays(7)->endOfDay();
        $startOfMonth = now()->startOfMonth();

        $totalAvailable = Planting::where('farmer_id', $farmerId)
            ->where('status', PlantingStatus::Available)
            ->count();

        $totalWeightAvailable = Planting::where('farmer_id', $farmerId)
            ->where('status', PlantingStatus::Available)
            ->sum('weight_kg');

        $expireSoon = Planting::where('farmer_id', $farmerId)
            ->where('status', PlantingStatus::Available)
            ->whereBetween('expiration_date', [$today, $sevenDaysFromNow])
            ->count();

        $postedThisMonth = Planting::where('farmer_id', $farmerId)
            ->where('status', PlantingStatus::Available)
            ->where('created_at', '>=', $startOfMonth)
            ->count();

        return [
            'total_available' => $totalAvailable,
            'total_weight_available' => round($totalWeightAvailable ?? 0, 2),
            'harvesting_soon' => $expireSoon,
            'posted_this_month' => $postedThisMonth,
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
        if ($planting->status !== PlantingStatus::Available) {
            throw new \LogicException('Only available plantings can be updated.');
        }

        if (isset($validated['expiration_date'])) {
            $expirationDate = Carbon::parse($validated['expiration_date']);
            
            if ($expirationDate->isPast()) {
                $validated['status'] = PlantingStatus::Archived;
            }
        }

        $planting->update($validated);
        
        return $planting->fresh();
    }

    public function markAsArchived(Planting $planting, ?float $actualWeight = null): void
    {
        if ($planting->isArchived()) {
            throw new \LogicException('Planting is already archived.');
        }

        $planting->markAsArchived($actualWeight);
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
