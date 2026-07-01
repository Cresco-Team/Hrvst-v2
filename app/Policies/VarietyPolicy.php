<?php

namespace App\Policies;

use App\Models\Product\Variety;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class VarietyPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function update(User $user, Variety $variety): bool
    {
        return $user->hasRole('admin');
    }

    public function delete(User $user, Variety $variety): bool
    {
        return $user->hasRole('admin');
    }
}
