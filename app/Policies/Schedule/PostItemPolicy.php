<?php

namespace App\Policies\Schedule;

use App\Enums\PostItemStatus;
use App\Models\Schedule\PostItem;
use App\Models\User;

class PostItemPolicy
{
    public function update(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id;
    }

    public function delete(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id
            && $postItem->status === PostItemStatus::Ongoing;
    }

    public function fulfill(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id
            && $postItem->status === PostItemStatus::Ongoing;
    }

    public function expire(User $user, PostItem $postItem): bool
    {
        return $user->id === $postItem->post->user_id
            && $postItem->status === PostItemStatus::Ongoing;
    }
}
