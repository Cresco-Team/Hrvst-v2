<?php

namespace App\Services\Admin;

use App\Models\Schedule\Post;
use App\Models\Profiles\DealerProfile;
use App\Models\Profiles\FarmerProfile;
use App\Services\Product\VegetableDetailService;

class DashboardService
{
    public function __construct(
        private VegetableDetailService $vegetableService,
        private FarmerService $farmerService,
        private DealerService $dealerService,
    ) {}

    public function getKPIs(): array
    {
        return [
            'farmers' => $this->getFarmerKPIs(),
            'dealers' => $this->getDealerKPIs(),
            'vegetables' => $this->getVarietyKPIs(),
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
        ];
    }

    private function getVarietyKPIs(): array
    {
        $current = $this->vegetableService->summary();

        return [
            'total_vegetables' => [
                'value' => $current['total_vegetables'],
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
