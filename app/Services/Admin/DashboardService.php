<?php

namespace App\Services\Admin;

use App\Models\Marketplace\DealerRequest;
use App\Models\Marketplace\FarmerOffering;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Services\Product\VarietyService;

class DashboardService
{
    public static function getKPIs(): array
    {
        return [
            'farmers' => self::getFarmerKPIs(),
            'dealers' => self::getDealerKPIs(),
            'varieties' => self::getVarietyKPIs(),
        ];
    }

    private static function getFarmerKPIs(): array
    {
        $current = FarmerService::summary();
        
        // Get previous period data (30 days ago)
        $previousTotal = FarmerProfile::where('is_approved', true)
            ->where('created_at', '<', now()->subDays(30))
            ->count();
        
        $previousOfferings = FarmerOffering::where('created_at', '<', now()->subDays(30))
            ->count();

        return [
            'total_farmers' => [
                'value' => $current['total_farmers'],
                'change' => self::calculatePercentageChange($previousTotal, $current['total_farmers']),
                'trend' => self::getTrend($previousTotal, $current['total_farmers']),
            ],
            'total_offerings' => [
                'value' => $current['total_offerings'],
                'change' => self::calculatePercentageChange($previousOfferings, $current['total_offerings']),
                'trend' => self::getTrend($previousOfferings, $current['total_offerings']),
            ],
        ];
    }

    private static function getDealerKPIs(): array
    {
        $current = DealerService::summary();
        
        $previousTotal = DealerProfile::where('is_approved', true)
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $previousRequests = DealerRequest::where('created_at', '<', now()->subDays(30))
            ->count();

        return [
            'total_dealers' => [
                'value' => $current['total_dealers'],
                'change' => self::calculatePercentageChange($previousTotal, $current['total_dealers']),
                'trend' => self::getTrend($previousTotal, $current['total_dealers']),
            ],
            'total_requests' => [
                'value' => $current['total_requests'],
                'change' => self::calculatePercentageChange($previousRequests, $current['total_requests']),
                'trend' => self::getTrend($previousRequests, $current['total_requests']),
            ],
        ];
    }

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

    private static function calculatePercentageChange(int $old, int $new): float
    {
        if ($old === 0) return $new > 0 ? 100.0 : 0.0;

        return round((($new - $old) / $old) * 100, 1);
    }

    private static function getTrend(int $old, int $new): string
    {
        if ($new > $old) return 'up';

        if ($new < $old) return 'down';

        return 'neutral';
    }
}
