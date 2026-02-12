<?php

namespace App\Policies\Product;

use App\Models\Product\Planting;
use App\Models\User;
use App\PlantingStatus;

class PlantingPolicy
{
    public function viewAny(User $user): bool
    {
        return ($user->hasRole('farmer') && $user->farmerProfile->is_approved)
            || ($user->hasRole('dealer') && $user->dealerProfile->is_approved)
            || $user->hasRole('admin');
    }

    public function view(User $user, Planting $planting): bool
    {
        return $planting->isAvailable() 
            && (($user->hasRole('farmer') && $user->farmerProfile->is_approved && $user->farmerProfile->id === $planting->farmer_id)
                || ($user->hasRole('dealer') && $user->dealerProfile->is_approved)
                || $user->hasRole('admin'));
    }

    public function create(User $user): bool
    {
        return $user->hasRole('farmer') 
            && $user->farmerProfile->is_approved;
    }

    public function update(User $user, Planting $planting): bool
    {
        return 
            $user->hasRole('farmer') 
            && $user->farmerProfile->is_approved
            && $user->farmerProfile->id === $planting->farmer_id
            && $planting->isAvailable();
    }

    public function delete(User $user, Planting $planting): bool
    {
        return $user->hasRole('farmer')
            && $user->farmerProfile->is_approved
            && $user->farmerProfile->id === $planting->farmer_id
            && $planting->status === PlantingStatus::Archived
            && !$planting->conversations()->exists();
    }

    public function destroy(User $user, Planting $planting): bool
    {
        return $this->delete($user, $planting)
            || $user->hasRole('admin');
    }
}
