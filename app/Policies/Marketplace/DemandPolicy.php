<?php

namespace App\Policies\Marketplace;

use App\Enums\DealerDemandStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\User;

class DemandPolicy
{
    public function viewAny(User $user): bool
    {
        // All approved users can view dealer demands
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved)
            || $user->hasRole('admin');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->dealerProfile && $user->dealerProfile->is_approved;
    }

    public function update(User $user, DealerDemand $demand): bool
    {
        return $user->dealerProfile?->id === $demand->dealer_id
            && $demand->status === DealerDemandStatus::Open;
    }

    public function delete(User $user, DealerDemand $dealerDemand): bool
    {
        return $user->dealerProfile?->id === $dealerDemand->dealer_id
            || $user->hasRole('admin');
    }

    public function react(User $user): bool
    {
        return $user->farmerProfile && $user->farmerProfile->is_approved;
    }

    public function markAsFulfilled(User $user, DealerDemand $demand): bool
    {
        return $user->dealerProfile?->id === $demand->dealer_id
            && $demand->status === DealerDemandStatus::Open;
    }

    public function flag(User $user): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }
}
