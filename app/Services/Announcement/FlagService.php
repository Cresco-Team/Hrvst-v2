<?php

namespace App\Services\Announcement;

use App\Models\Announcement\AnnouncementComment;
use App\Models\Announcement\AnnouncementFlag;
use App\Models\Announcement\DealerRequest;
use App\Models\Announcement\FarmerOffering;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class FlagService
{
    /**
     * Flag content as inappropriate
     */
    public function flag(
        User $user,
        string $flaggableType,
        int $flaggableId,
        string $reason,
        ?string $description = null
    ): AnnouncementFlag {
        // Validate flaggable exists
        $this->getFlaggable($flaggableType, $flaggableId);

        // Check if user already flagged this content
        $existing = AnnouncementFlag::where('user_id', $user->id)
            ->where('flaggable_type', "App\\Models\\Announcement\\{$flaggableType}")
            ->where('flaggable_id', $flaggableId)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new \LogicException('You have already flagged this content.');
        }

        return AnnouncementFlag::create([
            'user_id' => $user->id,
            'flaggable_type' => "App\\Models\\Announcement\\{$flaggableType}",
            'flaggable_id' => $flaggableId,
            'reason' => $reason,
            'description' => $description,
            'status' => 'pending',
        ]);
    }

    /**
     * Mark flag as reviewed (admin action)
     */
    public function review(AnnouncementFlag $flag): bool
    {
        if ($flag->status !== 'pending') {
            throw new \LogicException('Only pending flags can be reviewed.');
        }

        return $flag->update(['status' => 'reviewed']);
    }

    /**
     * Dismiss flag (admin action)
     */
    public function dismiss(AnnouncementFlag $flag): bool
    {
        if ($flag->status !== 'pending') {
            throw new \LogicException('Only pending flags can be dismissed.');
        }

        return $flag->update(['status' => 'dismissed']);
    }

    /**
     * Get pending flags with content details
     */
    public static function pending(int $perPage = 20): LengthAwarePaginator
    {
        return AnnouncementFlag::with(['user', 'flaggable'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function ($flag) {
                return [
                    'id' => $flag->id,
                    'flagger' => [
                        'id' => $flag->user->id,
                        'name' => $flag->user->name,
                    ],
                    'flaggable_type' => class_basename($flag->flaggable_type),
                    'flaggable_id' => $flag->flaggable_id,
                    'flaggable_preview' => self::getFlaggablePreview($flag->flaggable),
                    'reason' => $flag->reason,
                    'description' => $flag->description,
                    'status' => $flag->status,
                    'created_at' => $flag->created_at->format('M d, Y g:i A'),
                    'created_at_human' => $flag->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get flag summary statistics
     */
    public static function summary(): array
    {
        $pending = AnnouncementFlag::where('status', 'pending')->count();
        $reviewed = AnnouncementFlag::where('status', 'reviewed')->count();
        $dismissed = AnnouncementFlag::where('status', 'dismissed')->count();

        return [
            'pending' => $pending,
            'reviewed' => $reviewed,
            'dismissed' => $dismissed,
            'total' => $pending + $reviewed + $dismissed,
        ];
    }

    /**
     * Get flaggable model instance
     */
    private function getFlaggable(string $type, int $id): DealerRequest|FarmerOffering|AnnouncementComment
    {
        return match ($type) {
            'DealerRequest' => DealerRequest::findOrFail($id),
            'FarmerOffering' => FarmerOffering::findOrFail($id),
            'AnnouncementComment' => AnnouncementComment::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid flaggable type: {$type}"),
        };
    }

    /**
     * Get preview text for flagged content
     */
    private static function getFlaggablePreview($flaggable): string
    {
        return match (get_class($flaggable)) {
            DealerRequest::class => "Request for " . $flaggable->items->count() . " varieties on " . $flaggable->transaction_date->format('M d, Y'),
            FarmerOffering::class => "Offering: " . ($flaggable->variety?->vegetable?->name ?? 'Unknown') . " " . ($flaggable->variety?->name ?? ''),
            AnnouncementComment::class => substr($flaggable->comment, 0, 50) . (strlen($flaggable->comment) > 50 ? '...' : ''),
            default => 'Unknown content',
        };
    }
}
