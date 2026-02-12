<?php

namespace App\Policies\Product;

use App\Models\Product\Planting;
use App\Models\User;

class PlantingPolicy
{
    /**
     * Determine if the user can view the planting.
     */
    public function view(User $user, Planting $planting): bool
    {
        return $user->farmerProfile?->id === $planting->farmer_id ||
        $user->hasRole('dealer') || $user->hasRole('admin');
    }

    /**
     * Determine if the user can update the planting.
     * Only active plantings can be updated.
     */
    public function update(User $user, Planting $planting): bool
    {
        return $this->view($user, $planting) 
            && $planting->status === 'active';
    }

    /**
     * Determine if the user can delete the planting.
     * Cannot delete if dealers have inquired about it.
     */
    public function delete(User $user, Planting $planting): bool
    {
        return $this->view($user, $planting) 
            && !$planting->conversations()->exists();
    }

    /**
     * Determine if the user can mark the planting as harvested.
     */
    public function harvest(User $user, Planting $planting): bool
    {
        return $this->view($user, $planting) 
            && $planting->status === 'active';
    }

    /**
     * Determine if the user can mark the planting as cancelled.
     */
    public function cancel(User $user, Planting $planting): bool
    {
        return $this->view($user, $planting) 
            && $planting->status === 'active';
    }

    /**
     * Determine if the user can destroy the planting (admin override).
     */
    public function destroy(User $user, Planting $planting): bool
    {
        return $user->hasRole('admin') || $this->delete($user, $planting);
    }
}
