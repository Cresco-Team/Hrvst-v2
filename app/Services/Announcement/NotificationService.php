<?php

namespace App\Services\Announcement;

use App\Enums\DealerDemandStatus;
use App\FarmerOfferingStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Marketplace\FarmerOffering;
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
        );

        if ($dealers->isEmpty()) return 0;

        Notification::send($dealers, new MatchingOfferingAvailable($offering));

        return $dealers->count();
    }

    /**
     * Notify farmers when a dealer posts a demand that matches their crops
     */
    public function notifyMatchingFarmers(DealerDemand $demand): int
    {
        $varietyIds = $demand->pluck('variety_id')->unique();

        $farmers = $this->findFarmersWithVarieties(
            varietyIds: $varietyIds,
        );

        if ($farmers->isEmpty()) return 0;

        Notification::send($farmers, new MatchingRequestPosted($demand));

        return $farmers->count();
    }

    private function findDealersNeedingVariety(int $varietyId): Collection
    {
        return User::whereHas('dealerProfile', function ($query) {
            $query->where('is_approved', true);
        })->whereHas('dealerProfile.demands', function ($query) use ($varietyId) {
            $query->where('status', DealerDemandStatus::Open)
                ->whereIn('variety_id', $varietyId)
                ->where('transaction_date', '>=', now());
        })->get();
    }

    private function findFarmersWithVarieties(Collection $varietyIds): Collection
    {
        return User::whereHas('farmerProfile', function ($query) {
            $query->where('is_approved', true);
        })->whereHas('farmerProfile.offerings', function ($query) use ($varietyIds) {
            $query->where('status', FarmerOfferingStatus::Available)
                ->whereIn('variety_id', $varietyIds)
                ->where('expiration_date', '>=', now());
        })->get();
    }

    public function getMatchingOfferings(DealerDemand $demand): Collection
    {
        return FarmerOffering::with(['variety.vegetable', 'farmer.user', 'farmer.municipality'])
            ->where('variety_id', $demand->variety_id)
            ->where('status', FarmerOfferingStatus::Available)
            ->where('expiration_date', '>=', $demand->transaction_date)
            ->get();
    }

    public function getMatchingDemands(FarmerOffering $offering): Collection
    {
        return DealerDemand::with(['dealer.user', 'items.variety'])
            ->where('variey_id', $offering->variety_id)
            ->where('status', DealerDemandStatus::Open)
            ->where('transaction_date', '>=', now())
            ->get();
    }
}
