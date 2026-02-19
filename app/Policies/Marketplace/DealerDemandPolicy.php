<?php

namespace App\Policies\Marketplace;

use App\Enums\DealerDemandStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\User;

class DealerDemandPolicy
{
    /**
     * Determine if the user can view any dealer demands.
     */
    public function viewAny(User $user): bool
    {
        // All approved users can view dealer demands
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved)
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can view the dealer request.
     */
    public function view(User $user, DealerDemand $dealerDemand): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create dealer demands.
     */
    public function create(User $user): bool
    {
        return $user->dealerProfile && $user->dealerProfile->is_approved;
    }

    /**
     * Determine if the user can update the dealer request.
     */
    public function update(User $user, DealerDemand $dealerDemand): bool
    {
        return $user->dealerProfile?->id === $dealerDemand->dealer_id
            && $dealerDemand->status === DealerDemandStatus::Open;
    }

    /**
     * Determine if the user can delete the dealer request.
     */
    public function delete(User $user, DealerDemand $dealerDemand): bool
    {
        return $user->dealerProfile?->id === $dealerDemand->dealer_id
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can react to dealer demands.
     * Only farmers can react to dealer demands.
     */
    public function react(User $user, DealerDemand $dealerDemand): bool
    {
        return $user->farmerProfile && $user->farmerProfile->is_approved;
    }

    /**
     * Determine if the user can mark request as fulfilled.
     */
    public function markAsFulfilled(User $user, DealerDemand $dealerDemand): bool
    {
        return $user->dealerProfile?->id === $dealerDemand->dealer_id
            && $dealerDemand->status === DealerDemandStatus::Open;
    }

    /**
     * Determine if the user can flag the dealer request.
     */
    public function flag(User $user, DealerDemand $dealerDemand): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }
}
