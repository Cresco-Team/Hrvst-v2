<?php

namespace App\Policies\Announcement;

use App\FarmerOfferingStatus;
use App\Models\Marketplace\FarmerOffering;
use App\Models\User;

class FarmerOfferingPolicy
{
    /**
     * Determine if the user can view any farmer offerings.
     */
    public function viewAny(User $user): bool
    {
        // All approved users can view farmer offerings
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved)
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can view the farmer offering.
     */
    public function view(User $user, FarmerOffering $farmerOffering): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create farmer offerings.
     */
    public function create(User $user): bool
    {
        return $user->farmerProfile && $user->farmerProfile->is_approved;
    }

    /**
     * Determine if the user can update the farmer offering.
     */
    public function update(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->farmerProfile?->id === $farmerOffering->farmer_id
            && $farmerOffering->status === FarmerOfferingStatus::Available;
    }

    /**
     * Determine if the user can delete the farmer offering.
     */
    public function delete(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->farmerProfile?->id === $farmerOffering->farmer_id
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can comment on farmer offerings.
     * Only dealers can comment on farmer offerings.
     */
    public function comment(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->dealerProfile && $user->dealerProfile->is_approved;
    }

    /**
     * Determine if the user can react to farmer offerings.
     * All approved users can react.
     */
    public function react(User $user, FarmerOffering $farmerOffering): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }

    /**
     * Determine if the user can archive the offering.
     */
    public function archive(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->farmerProfile?->id === $farmerOffering->farmer_id;
    }

    /**
     * Determine if the user can flag the farmer offering.
     */
    public function flag(User $user, FarmerOffering $farmerOffering): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }
}
