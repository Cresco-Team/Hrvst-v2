<?php

namespace App\Policies\Marketplace;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->farmerProfile?->is_approved
            || $user->dealerProfile?->is_approved
            || $user->hasRole('admin');
    }

    public function view(User $user, Post $post): bool
    {
        return $user->hasRole('admin')
            || $user->dealerProfile?->is_approved
            || $user->farmerProfile?->is_approved;
    }

    /**
     * Called as: Gate::authorize('create', [Post::class, PostType::Supply])
     * or:        Gate::authorize('create', [Post::class, PostType::Demand])
     *
     * Laravel passes extra array elements as additional arguments to the policy method.
     */
    public function create(User $user, PostType $type): bool
    {
        return match ($type) {
            PostType::Supply => $user->hasRole('farmer') && $user->farmerProfile?->is_approved,
            PostType::Demand => $user->hasRole('dealer') && $user->dealerProfile?->is_approved,
        };
    }

    public function update(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            && $post->status === PostStatus::Ongoing;
    }

    public function archive(User $user, Post $post): bool
    {
        // Supply: must be Ongoing (same guard as update — can only archive what you can still edit)
        // Demand: only blocked if already Archived (can archive a Fulfilled demand for housekeeping)
        return match ($post->type) {
            PostType::Supply => $this->update($user, $post),
            PostType::Demand => $user->id === $post->user_id
                && $post->status !== PostStatus::Archived,
        };
    }

    public function fulfill(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            && $post->status !== PostStatus::Fulfilled;
    }

    public function delete(User $user, Post $post): bool
    {
        // Supply: owner OR admin can delete at any status (admin moderation use-case)
        // Demand: owner only, and only when not Ongoing (must archive/fulfill first)
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id || $user->hasRole('admin'),
            PostType::Demand => $user->id === $post->user_id
                && $post->status !== PostStatus::Ongoing,
        };
    }
}
