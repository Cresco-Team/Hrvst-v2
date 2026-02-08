<?php

namespace App\Policies\Announcement;

use App\Models\Announcement\AnnouncementComment;
use App\Models\User;

class AnnouncementCommentPolicy
{
    /**
     * Determine if the user can update the comment.
     */
    public function update(User $user, AnnouncementComment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * Determine if the user can delete the comment.
     */
    public function delete(User $user, AnnouncementComment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->hasRole('admin');
    }

    /**
     * Determine if the user can flag the comment.
     */
    public function flag(User $user, AnnouncementComment $comment): bool
    {
        return ($user->farmerProfile && $user->farmerProfile->is_approved)
            || ($user->dealerProfile && $user->dealerProfile->is_approved);
    }
}
