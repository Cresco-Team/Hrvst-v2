<?php

namespace App\Services\Admin;

use App\Models\Marketplace\DealerDemand;
use App\Models\Marketplace\FarmerSupply;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Services\Product\VarietyService;

class DashboardService
{
    public function __construct(
        private FarmerService $farmerService,
        private VarietyService $varietyService,
    ){}

    public function getKPIs(): array
    {
        return [
            'farmers' => $this->getFarmerKPIs(),
            'dealers' => self::getDealerKPIs(),
            'varieties' => $this->getVarietyKPIs(),
        ];
    }

    private function getFarmerKPIs(): array
    {
        $current = $this->farmerService->summary();
        
        // Get previous period data (30 days ago)
        $previousTotal = FarmerProfile::approved()
            ->where('created_at', '<', now()->subDays(30))
            ->count();
        
        $previousSupplies = FarmerSupply::where('created_at', '<', now()->subDays(30))
            ->count();

        return [
            'total_farmers' => [
                'value' => $current['total_farmers'],
                'change' => self::calculatePercentageChange($previousTotal, $current['total_farmers']),
                'trend' => self::getTrend($previousTotal, $current['total_farmers']),
            ],
            'total_supplies' => [
                'value' => $current['total_supplies'],
                'change' => self::calculatePercentageChange($previousSupplies, $current['total_supplies']),
                'trend' => self::getTrend($previousSupplies, $current['total_supplies']),
            ],
        ];
    }

    private static function getDealerKPIs(): array
    {
        $current = DealerService::summary();
        
        $previousTotal = DealerProfile::approved()
            ->where('created_at', '<', now()->subDays(30))
            ->count();

        $previousDemands = DealerDemand::where('created_at', '<', now()->subDays(30))
            ->count();

        return [
            'total_dealers' => [
                'value' => $current['total_dealers'],
                'change' => self::calculatePercentageChange($previousTotal, $current['total_dealers']),
                'trend' => self::getTrend($previousTotal, $current['total_dealers']),
            ],
            'total_demands' => [
                'value' => $current['total_demands'],
                'change' => self::calculatePercentageChange($previousDemands, $current['total_demands']),
                'trend' => self::getTrend($previousDemands, $current['total_demands']),
            ],
        ];
    }

    private function getVarietyKPIs(): array
    {
        $current = $this->varietyService->summary();

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
                'label' => 'Average harvest weeks',
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
