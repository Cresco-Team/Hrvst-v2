<?php

namespace App\Policies;

use App\Models\Profiles\DealerProfile;
use App\Models\User;

class DealerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->dealerProfile?->is_approved
        ;
    }

    public function view(User $user, DealerProfile $dealerProfile): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, DealerProfile $dealerProfile): bool
    {
        return $user->hasRole('admin')
            || $user->dealerProfile?->id === $dealerProfile->id;
    }

    public function approve(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, DealerProfile $dealerProfile): bool
    {
        return $this->update($user, $dealerProfile);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DealerProfile $dealerProfile): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DealerProfile $dealerProfile): bool
    {
        return $this->update($user, $dealerProfile);
    }
}
