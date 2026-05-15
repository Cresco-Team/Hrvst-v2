<?php

namespace App\Policies\Marketplace;

use App\Enums\PostItemStatus;
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
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id
                && $post->status === PostStatus::Growing
                && $post->target_month <= now()->addMonth()->format('Y-m'),
            PostType::Demand => $user->id === $post->user_id
                && $post->status === PostStatus::Harvested,
        };
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
                && in_array($post->status, [PostStatus::Harvested], true),
            PostType::Demand => $user->id === $post->user_id
                && $post->status !== PostStatus::Harvested,
        };
    }

    public function delete(User $user, Post $post): bool
    {
        return match ($post->type) {
            PostType::Supply => $user->id === $post->user_id || $user->hasRole('admin'),
            // Demand can only be deleted when no PostItems are still ongoing
            PostType::Demand => $user->id === $post->user_id
                && ! $post->postItems()
                    ->where('status', PostItemStatus::Ongoing->value)
                    ->exists(),
        };
    }
}
