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
        return $user->id === $post->user_id
            && $post->status === PostStatus::Growing; // supply only; demands use UpdateDemandAction which checks Ongoing
    }

    public function harvest(User $user, Post $post): bool
    {
        return $user->id === $post->user_id
            && $post->type === PostType::Supply
            && $post->status === PostStatus::Growing;
    }

    public function archive(User $user, Post $post): bool
    {
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id
                && in_array($post->status, [PostStatus::Growing, PostStatus::Ongoing], true),
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
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id || $user->hasRole('admin'),
            PostType::Demand => $user->id === $post->user_id
                && $post->status !== PostStatus::Ongoing,
        };
    }
}
