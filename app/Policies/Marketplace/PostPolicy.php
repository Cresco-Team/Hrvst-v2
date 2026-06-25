<?php

namespace App\Policies\Marketplace;

use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('farmer')
            || $user->hasRole('dealer')
            || $user->hasRole('admin');
    }

    public function view(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user, PostType $type): bool
    {
        return match ($type) {
            PostType::Supply => $user->hasRole('farmer') && $user->farmerProfile !== null,
            PostType::Demand => $user->hasRole('dealer') && $user->dealerProfile !== null,
        };
    }

    public function update(User $user, Post $post): bool
    {
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id,
            PostType::Demand => $user->id === $post->user_id
        };
    }

    public function archive(User $user, Post $post): bool
    {
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id,
            PostType::Demand => $user->id === $post->user_id,
        };
    }

    public function delete(User $user, Post $post): bool
    {
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id,
            PostType::Demand => $user->id === $post->user_id
        };
    }
}
