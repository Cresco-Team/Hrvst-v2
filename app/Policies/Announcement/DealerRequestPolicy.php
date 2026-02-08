<?php

namespace App\Policies\Announcement;

use App\Models\Announcement\DealerRequest;
use App\Models\User;

class DealerRequestPolicy
{
    /**
     * Determine if the user can view any dealer requests.
     */
    public function viewAny(User $user): bool
    {
        // All approved users can view dealer requests
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved)
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can view the dealer request.
     */
    public function view(User $user, DealerRequest $dealerRequest): bool
    {
        return $this->viewAny($user);
    }

    /**
     * Determine if the user can create dealer requests.
     */
    public function create(User $user): bool
    {
        return $user->dealerProfile && $user->dealerProfile->is_approved;
    }

    /**
     * Determine if the user can update the dealer request.
     */
    public function update(User $user, DealerRequest $dealerRequest): bool
    {
        return $user->dealerProfile?->id === $dealerRequest->dealer_id
            && $dealerRequest->status === 'open';
    }

    /**
     * Determine if the user can delete the dealer request.
     */
    public function delete(User $user, DealerRequest $dealerRequest): bool
    {
        return $user->dealerProfile?->id === $dealerRequest->dealer_id
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can react to dealer requests.
     * Only farmers can react to dealer requests.
     */
    public function react(User $user, DealerRequest $dealerRequest): bool
    {
        return $user->farmerProfile && $user->farmerProfile->is_approved;
    }

    /**
     * Determine if the user can mark request as fulfilled.
     */
    public function markAsFulfilled(User $user, DealerRequest $dealerRequest): bool
    {
        return $user->dealerProfile?->id === $dealerRequest->dealer_id
            && $dealerRequest->status === 'open';
    }

    /**
     * Determine if the user can flag the dealer request.
     */
    public function flag(User $user, DealerRequest $dealerRequest): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }
}
