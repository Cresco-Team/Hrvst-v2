<?php

namespace App\Http\Controllers;

use App\Enums\Billing\SubscriptionFeature;
use App\Models\Billing\Subscription;
use App\Notifications\VegetableOutlookAlert;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $feature = SubscriptionFeature::forUser($user);
        $hasAccess = $feature && Subscription::hasAccess($user, $feature);

        $notifications = $user->notifications()
            ->where('type', VegetableOutlookAlert::class)
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn ($n) => [
                'id' => $n->id,
                'vegetable_id' => $n->data['vegetable_id'],
                'vegetable_name' => $n->data['vegetable_name'],
                'band' => $n->data['band'],
                'message' => $hasAccess
                    ? $n->data['label']
                    : ucfirst($n->data['band']).' expected — subscribe for exact timing.',
                'detail_locked' => ! $hasAccess,
                'read_at' => $n->read_at,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        $unreadAlertCount = $user->where('type', VegetableOutlookAlert::class)->unreadNotifications();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadAlertCount,
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->where('id', $id)->update(['read_at' => now()]);

        return response()->json(['status' => 'ok']);
    }
}
