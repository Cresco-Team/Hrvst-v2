<?php

namespace App\Services\Announcement;

use App\Models\Announcement\DealerRequest;
use App\Models\Announcement\FarmerOffering;
use App\Models\Product\Planting;
use App\Models\User;
use App\Notifications\Announcement\MatchingOfferingAvailable;
use App\Notifications\Announcement\MatchingRequestPosted;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Notify dealers when a farmer posts an offering that matches their needs
     */
    public function notifyMatchingDealers(FarmerOffering $offering): int
    {
        $dealers = $this->findDealersNeedingVariety(
            varietyId: $offering->variety_id,
            quantityAvailable: (float) $offering->quantity_kg
        );

        if ($dealers->isEmpty()) {
            return 0;
        }

        Notification::send($dealers, new MatchingOfferingAvailable($offering));

        return $dealers->count();
    }

    /**
     * Notify farmers when a dealer posts a request that matches their crops
     */
    public function notifyMatchingFarmers(DealerRequest $request): int
    {
        $varietyIds = $request->items->pluck('variety_id')->unique();

        $farmers = $this->findFarmersWithVarieties(
            varietyIds: $varietyIds,
            transactionDate: $request->transaction_date
        );

        if ($farmers->isEmpty()) {
            return 0;
        }

        Notification::send($farmers, new MatchingRequestPosted($request));

        return $farmers->count();
    }

    /**
     * Find dealers who have open requests for this variety
     * 
     * Matching Criteria:
     * - Dealer has open request with this variety
     * - Transaction date is in the future
     * - Requested quantity <= available quantity (optional filter)
     */
    private function findDealersNeedingVariety(int $varietyId, float $quantityAvailable): Collection
    {
        return User::whereHas('dealerProfile', function ($query) {
            $query->where('is_approved', true);
        })
        ->whereHas('dealerProfile.requests', function ($query) use ($varietyId, $quantityAvailable) {
            $query->where('status', 'open')
                ->where('transaction_date', '>=', now())
                ->whereHas('items', function ($itemQuery) use ($varietyId, $quantityAvailable) {
                    $itemQuery->where('variety_id', $varietyId)
                        // Optional: Only notify if farmer has enough quantity
                        ->where('quantity_kg', '<=', $quantityAvailable);
                });
        })
        ->get();
    }

    /**
     * Find farmers who have active plantings for these varieties
     * 
     * Matching Criteria:
     * - Farmer has active planting with matching variety
     * - Planting is ready (harvesting soon or harvestable)
     * - Expected harvest date is before or near transaction date
     */
    private function findFarmersWithVarieties(Collection $varietyIds, $transactionDate): Collection
    {
        return User::whereHas('farmerProfile', function ($query) {
            $query->where('is_approved', true);
        })
        ->whereHas('farmerProfile.plantings', function ($query) use ($varietyIds, $transactionDate) {
            $query->where('status', 'active')
                ->whereIn('variety_id', $varietyIds)
                // Harvest date must be before transaction date (farmer can fulfill)
                ->where('expected_harvest_date', '<=', $transactionDate)
                // Harvest date shouldn't be too far in the past (still fresh)
                ->where('expected_harvest_date', '>=', now()->subDays(7));
        })
        ->get();
    }

    public function getMatchingPlantings(DealerRequest $request): Collection
    {
        $varietyIds = $request->items->pluck('variety_id')->unique();

        return Planting::with(['variety.vegetable', 'farmer.user', 'farmer.municipality'])
            ->where('status', 'active')
            ->whereIn('variety_id', $varietyIds)
            ->where('expected_harvest_date', '<=', $request->transaction_date)
            ->where('expected_harvest_date', '>=', now()->subDays(7))
            ->get();
    }

    public function getMatchingRequests(FarmerOffering $offering): Collection
    {
        return DealerRequest::with(['dealer.user', 'items.variety'])
            ->where('status', 'open')
            ->where('transaction_date', '>=', now())
            ->whereHas('items', function ($query) use ($offering) {
                $query->where('variety_id', $offering->variety_id)
                    ->where('quantity_kg', '<=', $offering->quantity_kg);
            })
            ->get();
    }
}
