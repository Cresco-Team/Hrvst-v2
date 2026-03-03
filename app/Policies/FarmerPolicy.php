<?php

namespace App\Policies;

use App\Models\Profiles\FarmerProfile;
use App\Models\User;

class FarmerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->farmerProfile?->is_approved
        ;
    }

    public function view(User $user, FarmerProfile $farmerProfile): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, FarmerProfile $farmerProfile): bool
    {
        return $user->hasRole('admin')
            || $user->farmerProfile?->id === $farmerProfile->id;
    }

    public function approve(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function reject(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, FarmerProfile $farmerProfile): bool
    {
        return $this->update($user, $farmerProfile);
    }

    public function restore(User $user, FarmerProfile $farmerProfile): bool
    {
        return $user->hasRole('admin');
    }

    public function forceDelete(User $user, FarmerProfile $farmerProfile): bool
    {
        return $this->update($user, $farmerProfile);
    }
}
