<?php

namespace App\Services\Admin;

use App\Models\Messaging\Conversation;
use App\Models\Messaging\Message;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Models\Product\Planting;
use App\Models\Product\Variety;
use App\Models\User;
use App\Services\Product\VarietyService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get all KPI metrics with historical comparison
     */
    public static function getKPIs(): array
    {
        return [
            'farmers' => self::getFarmerKPIs(),
            'dealers' => self::getDealerKPIs(),
            'varieties' => self::getVarietyKPIs(),
            'system' => self::getSystemKPIs(),
        ];
    }

    /**
     * Get all chart data for visualizations
     */
    public static function getChartData(): array
    {
        return [
            'plantings_timeline' => self::getPlantingsTimeline(),
            'harvest_forecast' => self::getHarvestForecast(),
            'conversation_activity' => self::getConversationActivity(),
            'variety_distribution' => self::getVarietyDistribution(),
        ];
    }

    /**
     * Farmer KPIs with historical comparison
     */
    private static function getFarmerKPIs(): array
    {
        $current = FarmerService::summary();
        
        // Get previous period data (30 days ago)
        $previousTotal = FarmerProfile::where('is_approved', true)
            ->where('created_at', '<', now()->subDays(30))
            ->count();
        
        $previousPlantings = Planting::where('status', 'active')
            ->where('date_planted', '<', now()->subDays(30))
            ->count();

        return [
            'total_farmers' => [
                'value' => $current['total_farmers'],
                'change' => self::calculatePercentageChange($previousTotal, $current['total_farmers']),
                'trend' => self::getTrend($previousTotal, $current['total_farmers']),
            ],
            'total_active_plantings' => [
                'value' => $current['total_active_plantings'],
                'change' => self::calculatePercentageChange($previousPlantings, $current['total_active_plantings']),
                'trend' => self::getTrend($previousPlantings, $current['total_active_plantings']),
            ],
            'harvesting_soon' => [
                'value' => $current['harvesting_soon'],
            ],
            'average_plantings_per_farmer' => [
                'value' => $current['average_plantings_per_farmer'],
            ],
        ];
    }

    /**
     * Dealer KPIs with historical comparison
     */
    private static function getDealerKPIs(): array
    {
        $current = DealerService::summary();
        
        $previousTotal = DealerProfile::where('is_approved', true)
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $previousConversations = Conversation::where('created_at', '<', now()->subDays(30))
            ->count();

        return [
            'total_dealers' => [
                'value' => $current['total_dealers'],
                'change' => self::calculatePercentageChange($previousTotal, $current['total_dealers']),
                'trend' => self::getTrend($previousTotal, $current['total_dealers']),
            ],
            'active_this_week' => [
                'value' => $current['active_this_week'],
                'label' => 'Active dealers',
            ],
            'total_conversations' => [
                'value' => $current['total_conversations'],
                'change' => self::calculatePercentageChange($previousConversations, $current['total_conversations']),
                'trend' => self::getTrend($previousConversations, $current['total_conversations']),
            ],
            'new_this_month' => [
                'value' => $current['new_this_month'],
            ],
        ];
    }

    /**
     * Variety KPIs with price freshness focus
     */
    private static function getVarietyKPIs(): array
    {
        $current = VarietyService::summary();

        return [
            'total_varieties' => [
                'value' => $current['total_varieties'],
            ],
            'price_updates_week' => [
                'value' => $current['price_stats']['updated_week'],
                'label' => 'Updated this week',
            ],
            'needs_attention' => [
                'value' => $current['price_stats']['stale'] + $current['price_stats']['no_price'],
                'label' => 'Stale/No price',
            ],
            'average_harvest_time' => [
                'value' => $current['average_weeks_to_harvest'],
                'label' => 'weeks',
            ],
        ];
    }

    /**
     * System-wide KPIs
     */
    private static function getSystemKPIs(): array
    {
        $totalUsers = User::count();
        $previousUsers = User::where('created_at', '<', now()->subDays(30))->count();

        $activeConversations = Conversation::where('last_message_at', '>=', now()->subDays(7))
            ->count();

        $totalMessages = Message::whereBetween('created_at', [now()->subDays(30), now()])
            ->count();

        $previousMessages = Message::whereBetween('created_at', [now()->subDays(60), now()->subDays(30)])
            ->count();

        return [
            'total_users' => [
                'value' => $totalUsers,
                'change' => self::calculatePercentageChange($previousUsers, $totalUsers),
                'trend' => self::getTrend($previousUsers, $totalUsers),
            ],
            'active_conversations' => [
                'value' => $activeConversations,
                'label' => 'Last 7 days',
            ],
            'messages_sent' => [
                'value' => $totalMessages,
                'change' => self::calculatePercentageChange($previousMessages, $totalMessages),
                'trend' => self::getTrend($previousMessages, $totalMessages),
            ],
        ];
    }

    /**
     * Get plantings timeline for last 30 days
     */
    private static function getPlantingsTimeline(): array
    {
        $data = Planting::selectRaw('DATE(date_planted) as date, COUNT(*) as count')
            ->where('date_planted', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing dates with zero
        $timeline = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $existing = $data->firstWhere('date', $date);
            
            $timeline[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'count' => $existing ? (int) $existing->count : 0,
            ];
        }

        return $timeline;
    }

    /**
     * Get harvest forecast for next 12 weeks grouped by category
     */
    private static function getHarvestForecast(): array
    {
        $forecast = [];
        
        for ($week = 0; $week < 12; $week++) {
            $startDate = now()->addWeeks($week)->startOfWeek();
            $endDate = now()->addWeeks($week)->endOfWeek();

            $data = Planting::query()
                ->join('varieties', 'plantings.variety_id', '=', 'varieties.id')
                ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
                ->join('categories', 'vegetables.category_id', '=', 'categories.id')
                ->whereBetween('expected_harvest_date', [$startDate, $endDate])
                ->where('status', 'active')
                ->selectRaw('categories.name as category, SUM(CAST(weight_kg AS DECIMAL(10,2))) as total_weight')
                ->groupBy('categories.id', 'categories.name')
                ->get();

            $weekData = [
                'week' => 'Week ' . ($week + 1),
                'date_range' => $startDate->format('M d') . ' - ' . $endDate->format('M d'),
            ];

            foreach ($data as $row) {
                $weekData[$row->category] = (float) $row->total_weight;
            }

            $forecast[] = $weekData;
        }

        return $forecast;
    }

    /**
     * Get conversation activity for last 30 days
     */
    private static function getConversationActivity(): array
    {
        $messages = Message::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $conversations = Conversation::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $activity = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            
            $activity[] = [
                'date' => Carbon::parse($date)->format('M d'),
                'messages' => $messages->get($date)?->count ?? 0,
                'conversations' => $conversations->get($date)?->count ?? 0,
            ];
        }

        return $activity;
    }

    /**
     * Get variety distribution (top 10 by active plantings)
     */
    private static function getVarietyDistribution(): array
    {
        $distribution = Planting::query()
            ->join('varieties', 'plantings.variety_id', '=', 'varieties.id')
            ->join('vegetables', 'varieties.vegetable_id', '=', 'vegetables.id')
            ->join('categories', 'vegetables.category_id', '=', 'categories.id')
            ->where('plantings.status', 'active')
            ->selectRaw('
                CONCAT(vegetables.name, " ", varieties.name) as variety_name,
                categories.name as category,
                COUNT(*) as count
            ')
            ->groupBy('varieties.id', 'vegetables.name', 'varieties.name', 'categories.name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return $distribution->map(fn($item) => [
            'name' => $item->variety_name,
            'category' => $item->category,
            'value' => (int) $item->count,
        ])->toArray();
    }

    /**
     * Calculate percentage change between two values
     */
    private static function calculatePercentageChange(int $old, int $new): float
    {
        if ($old === 0) {
            return $new > 0 ? 100.0 : 0.0;
        }

        return round((($new - $old) / $old) * 100, 1);
    }

    /**
     * Get trend direction
     */
    private static function getTrend(int $old, int $new): string
    {
        if ($new > $old) {
            return 'up';
        }

        if ($new < $old) {
            return 'down';
        }

        return 'neutral';
    }
}
