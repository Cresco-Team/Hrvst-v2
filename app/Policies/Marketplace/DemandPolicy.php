<?php

namespace App\Policies\Marketplace;

use App\Enums\DealerDemandStatus;
use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\User;

class DemandPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->farmerProfile?->is_approved
            || $user->dealerProfile?->is_approved
            || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('dealer') 
            && $user->dealerProfile?->is_approved;
    }

    public function update(User $user, DealerDemand $demand): bool
    {
        return $user->dealerProfile?->id === $demand->dealer_id
            && $demand->post->status === PostStatus::Ongoing;
    }

    public function archive(User $user, DealerDemand $demand): bool
    {
        return $this->update($user, $demand);
    }

    public function fulfill(User $user, DealerDemand $demand): bool
    {
        return $this->update($user, $demand);
    }

    public function delete(User $user, DealerDemand $demand): bool
    {
        return $this->update($user, $demand);
    }
}
