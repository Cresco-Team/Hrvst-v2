<?php

namespace App\Policies\Announcement;

use App\FarmerOfferingStatus;
use App\Models\Marketplace\FarmerOffering;
use App\Models\User;

class FarmerOfferingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->farmerProfile?->is_approved
            || $user->dealerProfile?->is_approved
            || $user->hasRole('admin');
    }

    public function view(User $user, FarmerOffering $farmerOffering): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->farmerProfile && $user->farmerProfile->is_approved;
    }

    public function update(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->farmerProfile?->id === $farmerOffering->farmer_id
            && $farmerOffering->status === FarmerOfferingStatus::Available;
    }

    public function delete(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->farmerProfile?->id === $farmerOffering->farmer_id
            || $user->hasRole('admin');
    }

    public function comment(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->dealerProfile && $user->dealerProfile->is_approved;
    }

    public function react(User $user, FarmerOffering $farmerOffering): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }

    public function archive(User $user, FarmerOffering $farmerOffering): bool
    {
        return $user->farmerProfile?->id === $farmerOffering->farmer_id;
    }

    public function flag(User $user, FarmerOffering $farmerOffering): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }
}
