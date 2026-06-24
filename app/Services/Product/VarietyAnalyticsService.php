<?php

namespace App\Services\Product;

use App\DTOs\Product\VarietyAnalyticsDTO;
use App\DTOs\Product\VarietyRecommendationDTO;
use App\Enums\Analytics\ImbalanceBand;
use App\Enums\Analytics\RecommendationSeverity;
use App\Enums\Analytics\VarietyViewerRole;

class VarietyAnalyticsService
{
    private const float OVERSUPPLY_THRESHOLD = 0.20;

    private const float UNDERSUPPLY_THRESHOLD = -0.20;

    private const float LOW_FULFILLMENT_THRESHOLD = 0.50;

    private const float SUPPLY_DECLINE_THRESHOLD = -20.0;

    /**
     * @param  array<int, array{
     *     month: string,
     *     label: string,
     *     supply_expired_kg: float,
     *     supply_fulfilled_kg: float,
     *     demand_expired_kg: float,
     *     demand_fulfilled_kg: float,
     * }> $monthlyActivity
     */
    public function compute(
        array $monthlyActivity,
        VarietyViewerRole $role,
    ): VarietyAnalyticsDTO {
        $completeMonths = array_slice($monthlyActivity, -4, 3);

        $ratio = $this->computeImbalanceRatio($completeMonths);
        $band = $this->classifyBand($ratio);
        $supplyFulfillment = $this->computeFulfillmentRate($completeMonths, 'supply');
        $demandFulfillment = $this->computeFulfillmentRate($completeMonths, 'demand');

        [$supplyMomPct, $demandMomPct] = $this->computeVolumeMonthOverMonth($monthlyActivity);

        $recommendations = $this->buildRecommendations(
            band: $band,
            supplyFulfillment: $supplyFulfillment,
            demandFulfillment: $demandFulfillment,
            supplyMomPct: $supplyMomPct,
            role: $role,
        );

        return new VarietyAnalyticsDTO(
            supply_demand_ratio: $ratio,
            imbalance_band: $band,
            supply_fulfillment_rate: $supplyFulfillment,
            demand_fulfillment_rate: $demandFulfillment,
            supply_volume_mom_pct: $supplyMomPct,
            demand_volume_mom_pct: $demandMomPct,
            recommendations: $recommendations,
        );
    }

    private function computeImbalanceRatio(array $months): float
    {
        if (empty($months)) {
            return 0.0;
        }

        $totalSupply = array_sum(array_map(
            fn (array $m) => $m['supply_fulfilled_kg'] + $m['supply_expired_kg'],
            $months,
        ));

        $totalDemand = array_sum(array_map(
            fn (array $m) => $m['demand_fulfilled_kg'] + $m['demand_expired_kg'],
            $months,
        ));

        $count = count($months);
        $avgSupply = $totalSupply / $count;
        $avgDemand = $totalDemand / $count;

        return round(($avgSupply - $avgDemand) / max($avgDemand, 1.0), 4);
    }

    private function classifyBand(float $ratio): ImbalanceBand
    {
        return match (true) {
            $ratio > self::OVERSUPPLY_THRESHOLD => ImbalanceBand::Oversupply,
            $ratio < self::UNDERSUPPLY_THRESHOLD => ImbalanceBand::Undersupply,
            default => ImbalanceBand::Balanced,
        };
    }

    /** @param 'supply'|'demand' $type */
    private function computeFulfillmentRate(array $months, string $type): ?float
    {
        $fulfilled = (float) array_sum(array_map(
            fn (array $m) => $m["{$type}_fulfilled_kg"],
            $months,
        ));

        $expired = (float) array_sum(array_map(
            fn (array $m) => $m["{$type}_expired_kg"],
            $months,
        ));

        $total = $fulfilled + $expired;

        return $total > 0.0 ? round($fulfilled / $total, 4) : null;
    }

    /** @return array{?float, ?float} */
    private function computeVolumeMonthOverMonth(array $monthlyActivity): array
    {
        $count = count($monthlyActivity);

        $lastMonth = $monthlyActivity[$count - 2] ?? null;
        $prevMonth = $monthlyActivity[$count - 3] ?? null;

        if ($lastMonth === null || $prevMonth === null) {
            return [null, null];
        }

        $lastSupply = $lastMonth['supply_fulfilled_kg'] + $lastMonth['supply_expired_kg'];
        $prevSupply = $prevMonth['supply_fulfilled_kg'] + $prevMonth['supply_expired_kg'];
        $lastDemand = $lastMonth['demand_fulfilled_kg'] + $lastMonth['demand_expired_kg'];
        $prevDemand = $prevMonth['demand_fulfilled_kg'] + $prevMonth['demand_expired_kg'];

        $supplyMom = $prevSupply > 0.0
            ? round((($lastSupply - $prevSupply) / $prevSupply) * 100, 2)
            : null;

        $demandMom = $prevDemand > 0.0
            ? round((($lastDemand - $prevDemand) / $prevDemand) * 100, 2)
            : null;

        return [$supplyMom, $demandMom];
    }

    /** @return VarietyRecommendationDTO[] */
    private function buildRecommendations(
        ImbalanceBand $band,
        ?float $supplyFulfillment,
        ?float $demandFulfillment,
        ?float $supplyMomPct,
        VarietyViewerRole $role,
    ): array {
        $recs = [];

        if ($band === ImbalanceBand::Undersupply) {
            $body = match ($role) {
                VarietyViewerRole::Admin => 'Dealer demand is outpacing available supply. Consider prompting more farmers to post.',
                VarietyViewerRole::Marketplace => 'Buyers are actively looking for this variety. Good time to post your available harvest.',
            };

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'supply_opportunity',
                title: 'Unfulfilled Dealer Demand',
                body: $body,
            );
        }

        if ($supplyFulfillment !== null && $supplyFulfillment < self::LOW_FULFILLMENT_THRESHOLD) {
            $expiredPct = (int) round((1 - $supplyFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'high_supply_expiry_rate',
                title: 'High Supply Expiry Rate',
                body: "{$expiredPct}% of supply posts over the last 3 months expired without a match. "
                    .'This typically indicates a delivery timing mismatch with buyers.',
            );
        }

        if ($demandFulfillment !== null && $demandFulfillment < self::LOW_FULFILLMENT_THRESHOLD) {
            $expiredPct = (int) round((1 - $demandFulfillment) * 100);

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Warning,
                type: 'high_demand_expiry_rate',
                title: 'Low Demand Fulfillment',
                body: "{$expiredPct}% of demand posts expired unfulfilled over the last 3 months. "
                    .'Dealers are not finding adequate supply to match their requirements.',
            );
        }

        if ($supplyMomPct !== null && $supplyMomPct < self::SUPPLY_DECLINE_THRESHOLD) {
            $dropPct = (int) round(abs($supplyMomPct));

            $recs[] = new VarietyRecommendationDTO(
                severity: RecommendationSeverity::Info,
                type: 'declining_supply_volume',
                title: 'Supply Volume Declining',
                body: "Supply volume dropped {$dropPct}% compared to last month. "
                    .'Monitor whether this is seasonal or signals a structural reduction.',
            );
        }

        usort(
            $recs,
            fn (VarietyRecommendationDTO $a, VarietyRecommendationDTO $b) => $this->severityOrder($a->severity) <=> $this->severityOrder($b->severity),
        );

        return $recs;
    }

    private function severityOrder(RecommendationSeverity $severity): int
    {
        return match ($severity) {
            RecommendationSeverity::Critical => 0,
            RecommendationSeverity::Warning => 1,
            RecommendationSeverity::Info => 2,
        };
    }
}
