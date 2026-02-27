<?php

namespace App\Policies\Marketplace;

use App\Enums\PostStatus;
use App\Models\Marketplace\FarmerSupply;
use App\Models\User;

class SupplyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->farmerProfile?->is_approved
            || $user->dealerProfile?->is_approved
            || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('farmer')
            && $user->farmerProfile?->is_approved;
    }

    public function update(User $user, FarmerSupply $supply): bool
    {
        return $user->farmerProfile?->id === $supply->farmer_id
            && $supply->post->status === PostStatus::Ongoing;
    }

    public function archive(User $user, FarmerSupply $supply): bool
    {
        return $this->update($user, $supply);
    }

    public function fulfill(User $user, FarmerSupply $supply): bool
    {
        return $user->farmerProfile?->id === $supply->farmer_id;
    }

    public function delete(User $user, FarmerSupply $supply): bool
    {
        return $user->farmerProfile?->id === $supply->farmer_id
            || $user->hasRole('admin');
    }
}
