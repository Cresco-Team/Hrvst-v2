<?php

namespace App\Http\Controllers\Announcement;

use App\Http\Controllers\Controller;
use App\Models\Announcement\AnnouncementComment;
use App\Models\Announcement\FarmerOffering;
use App\Services\Announcement\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(
        private CommentService $service
    ) {}

    /**
     * Get comments for an offering
     */
    public function index(Request $request, FarmerOffering $farmerOffering): JsonResponse
    {
        $comments = CommentService::forOffering($farmerOffering->id);

        return response()->json($comments);
    }

    /**
     * Store a new comment
     */
    public function store(Request $request, FarmerOffering $farmerOffering): JsonResponse
    {
        Gate::authorize('comment', $farmerOffering);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000', 'min:1'],
        ]);

        $comment = $this->service->create(
            offering: $farmerOffering,
            user: $request->user(),
            comment: $validated['comment']
        );

        // Load relationships for response
        $comment->load('user');

        return response()->json([
            'comment' => [
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
                'can_delete' => $request->user()->can('delete', $comment),
            ],
        ], 201);
    }

    /**
     * Update a comment
     */
    public function update(Request $request, AnnouncementComment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:1000', 'min:1'],
        ]);

        $updatedComment = $this->service->update($comment, $validated['comment']);

        return response()->json([
            'message' => 'Comment updated',
            'comment' => [
                'id' => $updatedComment->id,
                'comment' => $updatedComment->comment,
            ],
        ]);
    }

    /**
     * Delete a comment
     */
    public function destroy(AnnouncementComment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $this->service->delete($comment);

        return response()->json([
            'message' => 'Comment deleted',
        ]);
    }
}
