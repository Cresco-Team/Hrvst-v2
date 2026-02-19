<?php

namespace App\Http\Controllers\Announcement;

use App\Http\Controllers\Controller;
use App\Services\Announcement\ReactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function __construct(
        private ReactionService $service
    ) {}

    /**
     * Toggle reaction (create, update, or delete)
     */
    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactionable_type' => ['required', 'in:DealerDemand,FarmerOffering'],
            'reactionable_id' => ['required', 'integer'],
            'reaction_type' => ['required', 'string', 'max:20'],
        ]);

        $result = $this->service->toggle(
            user: $request->user(),
            reactionableType: $validated['reactionable_type'],
            reactionableId: $validated['reactionable_id'],
            reactionType: $validated['reaction_type']
        );

        // Get updated counts
        $counts = $this->service->getCounts(
            $validated['reactionable_type'],
            $validated['reactionable_id']
        );

        return response()->json([
            'action' => $result['action'],
            'reaction_counts' => $counts,
            'user_reaction' => $result['reaction']?->reaction_type,
        ]);
    }

    /**
     * Get current reaction state
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'reactionable_type' => ['required', 'in:DealerDemand,FarmerOffering'],
            'reactionable_id' => ['required', 'integer'],
        ]);

        $counts = $this->service->getCounts(
            $validated['reactionable_type'],
            $validated['reactionable_id']
        );

        $userReaction = $this->service->getUserReaction(
            $request->user(),
            $validated['reactionable_type'],
            $validated['reactionable_id']
        );

        return response()->json([
            'reaction_counts' => $counts,
            'user_reaction' => $userReaction,
        ]);
    }
}
