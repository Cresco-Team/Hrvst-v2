<?php

namespace App\Policies\Planting;

use App\Models\Product\Planting;
use App\Models\User;

class PlantingPolicy
{
    /**
     * Determine if the user can view the planting.
     */
    public function view(User $user, Planting $planting): bool
    {
        return $user->id === $planting->farmer->user_id;
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
}
