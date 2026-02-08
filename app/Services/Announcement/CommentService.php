<?php

namespace App\Services\Announcement;

use App\Models\Announcement\AnnouncementComment;
use App\Models\Announcement\FarmerOffering;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class CommentService
{
    /**
     * Create a new comment
     */
    public function create(
        FarmerOffering $offering,
        User $user,
        string $comment
    ): AnnouncementComment {
        Gate::authorize('comment', $offering);

        return AnnouncementComment::create([
            'farmer_offering_id' => $offering->id,
            'user_id' => $user->id,
            'comment' => $comment,
        ]);
    }

    /**
     * Update comment text
     */
    public function update(
        AnnouncementComment $comment,
        string $newComment
    ): AnnouncementComment {
        Gate::authorize('update', $comment);

        $comment->update(['comment' => $newComment]);

        return $comment->fresh();
    }

    /**
     * Delete comment
     */
    public function delete(AnnouncementComment $comment): bool
    {
        Gate::authorize('delete', $comment);

        return $comment->delete();
    }

    /**
     * Get paginated comments for an offering
     */
    public static function forOffering(int $offeringId, int $perPage = 50): LengthAwarePaginator
    {
        return AnnouncementComment::with(['user'])
            ->where('farmer_offering_id', $offeringId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function ($comment) {
                return [
                    'id' => $comment->id,
                    'user' => [
                        'id' => $comment->user->id,
                        'name' => $comment->user->name,
                        'avatar' => $comment->user->user_image,
                        'role' => $comment->user->hasRole('dealer') ? 'dealer' : ($comment->user->hasRole('farmer') ? 'farmer' : 'user'),
                    ],
                    'comment' => $comment->comment,
                    'created_at' => $comment->created_at->toISOString(),
                    'created_at_human' => $comment->created_at->diffForHumans(),
                    'can_delete' => Auth::check() && Auth::user()->can('delete', $comment),
                ];
            });
    }

    /**
     * Get comment count for an offering
     */
    public static function countForOffering(int $offeringId): int
    {
        return AnnouncementComment::where('farmer_offering_id', $offeringId)->count();
    }
}
