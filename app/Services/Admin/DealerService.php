<?php

namespace App\Services\Admin;

use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class DealerService
{
    /**
     * Get paginated list of approved dealers with their activity metrics
     */
    public static function paginated(int $perPage = 20): LengthAwarePaginator
    {
        return DealerProfile::with(['user'])
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function ($dealer) {
                // Get total conversations count
                $totalConversations = DB::table('conversation_participants')
                    ->where('user_id', $dealer->user_id)
                    ->distinct('conversation_id')
                    ->count('conversation_id');

                // Get active conversations (with messages in last 30 days)
                $activeConversations = DB::table('conversation_participants')
                    ->where('user_id', $dealer->user_id)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('messages')
                            ->whereColumn('messages.conversation_id', 'conversation_participants.conversation_id')
                            ->where('messages.created_at', '>=', now()->subDays(30));
                    })
                    ->distinct('conversation_id')
                    ->count('conversation_id');

                // Get last activity (last message sent by this dealer)
                $lastActivity = DB::table('messages')
                    ->where('sender_id', $dealer->user_id)
                    ->latest('created_at')
                    ->first(['created_at']);

                // Determine status based on activity
                $status = 'inactive';
                if ($lastActivity) {
                    $daysSinceActivity = now()->diffInDays($lastActivity->created_at);
                    if ($daysSinceActivity <= 7) {
                        $status = 'active';
                    } elseif ($daysSinceActivity <= 30) {
                        $status = 'moderate';
                    }
                }

                return [
                    'id' => $dealer->id,
                    'user' => [
                        'id' => $dealer->user->id,
                        'name' => $dealer->user->name,
                        'email' => $dealer->user->email,
                        'phone_number' => $dealer->user->phone_number,
                        'user_image' => $dealer->user->user_image,
                    ],
                    'activity' => [
                        'total_conversations' => $totalConversations,
                        'active_conversations' => $activeConversations,
                        'last_activity_at' => $lastActivity ? $lastActivity->created_at : null,
                        'last_activity_human' => $lastActivity 
                            ? \Carbon\Carbon::parse($lastActivity->created_at)->diffForHumans() 
                            : null,
                        'status' => $status,
                    ],
                    'document_image' => $dealer->document_image,
                    'joined_at' => $dealer->created_at->format('M d, Y'),
                    'joined_at_human' => $dealer->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get summary statistics for dealers
     */
    public static function summary(): array
    {
        $totalDealers = DealerProfile::where('is_approved', true)->count();

        // Active this week (sent messages in last 7 days)
        $activeThisWeek = DB::table('dealer_profiles')
            ->where('is_approved', true)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('messages')
                    ->whereColumn('messages.sender_id', 'dealer_profiles.user_id')
                    ->where('messages.created_at', '>=', now()->subWeek());
            })
            ->count();

        // Total conversations by all dealers
        $totalConversations = DB::table('conversation_participants')
            ->join('dealer_profiles', 'conversation_participants.user_id', '=', 'dealer_profiles.user_id')
            ->where('dealer_profiles.is_approved', true)
            ->distinct('conversation_participants.conversation_id')
            ->count('conversation_participants.conversation_id');

        // New dealers this month
        $newThisMonth = DealerProfile::where('is_approved', true)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total_dealers' => $totalDealers,
            'active_this_week' => $activeThisWeek,
            'total_conversations' => $totalConversations,
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * Get detailed dealer information
     */
    public static function find(int $dealerId): ?array
    {
        $dealer = DealerProfile::with(['user'])
            ->where('is_approved', true)
            ->find($dealerId);

        if (!$dealer) {
            return null;
        }

        // Get total conversations count
        $totalConversations = DB::table('conversation_participants')
            ->where('user_id', $dealer->user_id)
            ->distinct('conversation_id')
            ->count('conversation_id');

        // Get active conversations (with messages in last 30 days)
        $activeConversations = DB::table('conversation_participants')
            ->where('user_id', $dealer->user_id)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('messages')
                    ->whereColumn('messages.conversation_id', 'conversation_participants.conversation_id')
                    ->where('messages.created_at', '>=', now()->subDays(30));
            })
            ->distinct('conversation_id')
            ->count('conversation_id');

        // Get last activity
        $lastActivity = DB::table('messages')
            ->where('sender_id', $dealer->user_id)
            ->latest('created_at')
            ->first(['created_at']);

        // Calculate status
        $status = 'inactive';
        if ($lastActivity) {
            $daysSinceActivity = now()->diffInDays($lastActivity->created_at);
            if ($daysSinceActivity <= 7) {
                $status = 'active';
            } elseif ($daysSinceActivity <= 30) {
                $status = 'moderate';
            }
        }

        // Get favorite categories (most inquired vegetables)
        $favoriteCategories = DB::table('conversation_participants')
            ->join('conversations', 'conversation_participants.conversation_id', '=', 'conversations.id')
            ->join('plantings', 'conversations.planting_id', 'plantings.id')
            ->join('varieties', 'plantings.variety_id', '=', 'varieties.id')
            ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
            ->join('categories', 'vegetables.category_id', '=', 'categories.id')
            ->where('conversation_participants.user_id', $dealer->user_id)
            ->select('categories.name', DB::raw('COUNT(DISTINCT conversations.id) as count'))
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('count')
            ->limit(3)
            ->get();

        // Get recent conversations
        $recentConversations = DB::table('conversation_participants')
            ->join('conversations', 'conversation_participants.conversation_id', '=', 'conversations.id')
            ->leftJoin('messages', function ($join) {
                $join->on('messages.conversation_id', '=', 'conversations.id')
                    ->whereRaw('messages.id = (SELECT id FROM messages WHERE conversation_id = conversations.id ORDER BY created_at DESC LIMIT 1)');
            })
            ->leftJoin('plantings', 'conversations.planting_id', '=', 'plantings.id')
            ->leftJoin('varieties', 'plantings.variety_id', '=', 'varieties.id')
            ->leftJoin('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
            ->leftJoin('categories', 'vegetables.category_id', '=', 'categories.id')
            ->leftJoin('farmer_profiles', 'plantings.farmer_id', '=', 'farmer_profiles.id')
            ->leftJoin('users as farmers', 'farmer_profiles.user_id', '=', 'farmers.id')
            ->where('conversation_participants.user_id', $dealer->user_id)
            ->select([
                'conversations.id',
                DB::raw('CONCAT(vegetables.name, " ", varieties.name) as variety_name'),
                'categories.name as category_name',
                'farmers.name as farmer_name',
                'messages.message as last_message',
                'messages.created_at as last_message_at'
            ])
            ->orderByDesc('messages.created_at')
            ->limit(10)
            ->get();

        return [
            'id' => $dealer->id,
            'user' => [
                'id' => $dealer->user->id,
                'name' => $dealer->user->name,
                'email' => $dealer->user->email,
                'phone_number' => $dealer->user->phone_number,
                'user_image' => $dealer->user->user_image,
            ],
            'activity' => [
                'total_conversations' => $totalConversations,
                'active_conversations' => $activeConversations,
                'last_activity_at' => $lastActivity ? $lastActivity->created_at : null,
                'last_activity_human' => $lastActivity 
                    ? \Carbon\Carbon::parse($lastActivity->created_at)->diffForHumans() 
                    : null,
                'status' => $status,
                'favorite_categories' => $favoriteCategories->map(function ($cat) {
                    return [
                        'name' => $cat->name,
                        'count' => $cat->count,
                    ];
                })->toArray(),
            ],
            'recent_conversations' => $recentConversations->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'planting' => [
                        'variety_name' => $conversation->variety_name,
                        'category' => $conversation->category_name,
                        'farmer_name' => $conversation->farmer_name,
                    ],
                    'last_message' => $conversation->last_message,
                    'last_message_at' => $conversation->last_message_at 
                        ? \Carbon\Carbon::parse($conversation->last_message_at)->diffForHumans()
                        : null,
                ];
            })->toArray(),
            'document_image' => $dealer->document_image,
            'joined_at' => $dealer->created_at->format('M d, Y'),
            'joined_at_human' => $dealer->created_at->diffForHumans(),
        ];
    }
}
