<?php

namespace App\Services\Admin;

use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Services\Product\VarietyService;

class DashboardService
{
    public function __construct(
        private VarietyService $varietyService,
        private FarmerService $farmerService,
        private DealerService $dealerService,
    ) {}

    public function getKPIs(): array
    {
        return [
            'farmers' => $this->getFarmerKPIs(),
            'dealers' => $this->getDealerKPIs(),
            'varieties' => $this->getVarietyKPIs(),
        ];
    }

    private function getFarmerKPIs(): array
    {
        $current = $this->farmerService->summary();

        $previousTotal = FarmerProfile::where('created_at', '<', now()->subDays(30))->count();
        $previousSupplies = Post::supply()->where('created_at', '<', now()->subDays(30))->count();

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

    private function getDealerKPIs(): array
    {
        $current = $this->dealerService->summary();

        $previousTotal = DealerProfile::where('created_at', '<', now()->subDays(30))->count();
        $previousDemands = Post::demand()->where('created_at', '<', now()->subDays(30))->count();

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
        ];
    }

    private static function calculatePercentageChange(int $previous, int $current): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private static function getTrend(int $previous, int $current): string
    {
        return match (true) {
            $current > $previous => 'up',
            $current < $previous => 'down',
            default => 'flat',
        };
    }
}
