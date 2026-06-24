<?php

namespace App\Policies\Marketplace;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\PostItem;
use App\Models\User;

class PostItemPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, PostItem $postItem): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id;
    }

    public function delete(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id
            && $postItem->status === PostItemStatus::Ongoing;
    }

    public function restore(User $user, PostItem $postItem): bool
    {
        return false;
    }

    public function forceDelete(User $user, PostItem $postItem): bool
    {
        return false;
    }

    public function fulfill(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id
            && $postItem->status === PostItemStatus::Expired;
    }

    public function archive(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id
            && $postItem->status === PostItemStatus::Fulfilled;
    }
}
