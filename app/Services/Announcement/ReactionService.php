<?php

namespace App\Services\Announcement;

use App\Models\Marketplace\FarmerOffering;
use App\Models\Interaction\Reaction;
use App\Models\Marketplace\DealerDemand;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class ReactionService
{
    /**
     * Toggle reaction (create, update, or delete)
     * 
     * @return array ['action' => 'created'|'updated'|'deleted', 'reaction' => Reaction|null]
     */
    public function toggle(
        User $user,
        string $reactionableType,
        int $reactionableId,
        string $reactionType
    ): array {
        // Get the reactionable model
        $reactionable = $this->getReactionable($reactionableType, $reactionableId);

        // Authorize based on type
        if ($reactionableType === 'DealerDemand') {
            Gate::authorize('react', $reactionable);
        } elseif ($reactionableType === 'FarmerOffering') {
            Gate::authorize('react', $reactionable);
        }

        // Find existing reaction
        $existing = Reaction::where('user_id', $user->id)
            ->where('reactionable_type', "App\\Models\\Announcement\\{$reactionableType}")
            ->where('reactionable_id', $reactionableId)
            ->first();

        // If user already reacted with SAME type -> Delete (untoggle)
        if ($existing && $existing->reaction_type === $reactionType) {
            $existing->delete();
            return ['action' => 'deleted', 'reaction' => null];
        }

        // If user already reacted with DIFFERENT type -> Update
        if ($existing && $existing->reaction_type !== $reactionType) {
            $existing->update(['reaction_type' => $reactionType]);
            return ['action' => 'updated', 'reaction' => $existing];
        }

        // If user hasn't reacted -> Create
        $reaction = Reaction::create([
            'user_id' => $user->id,
            'reactionable_type' => "App\\Models\\Announcement\\{$reactionableType}",
            'reactionable_id' => $reactionableId,
            'reaction_type' => $reactionType,
        ]);

        return ['action' => 'created', 'reaction' => $reaction];
    }

    /**
     * Get reaction counts for a reactionable
     */
    public function getCounts(string $reactionableType, int $reactionableId): array
    {
        $reactions = Reaction::where('reactionable_type', "App\\Models\\Announcement\\{$reactionableType}")
            ->where('reactionable_id', $reactionableId)
            ->get()
            ->groupBy('reaction_type')
            ->map(fn($group) => $group->count())
            ->toArray();

        return $reactions;
    }

    /**
     * Get user's current reaction
     */
    public function getUserReaction(User $user, string $reactionableType, int $reactionableId): ?string
    {
        $reaction = Reaction::where('user_id', $user->id)
            ->where('reactionable_type', "App\\Models\\Announcement\\{$reactionableType}")
            ->where('reactionable_id', $reactionableId)
            ->first();

        return $reaction?->reaction_type;
    }

    /**
     * Get the reactionable model instance
     */
    private function getReactionable(string $type, int $id): DealerDemand|FarmerOffering
    {
        return match ($type) {
            'DealerDemand' => DealerDemand::findOrFail($id),
            'FarmerOffering' => FarmerOffering::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid reactionable type: {$type}"),
        };
    }
}
