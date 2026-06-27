<?php

namespace App\Services\Product;

use App\Enums\Analytics\VarietyViewerRole;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Support\Facades\DB;

class VarietyService
{
    public function __construct(
        private VarietyActivityService $activityService,
        private VarietyCalendarService $calendarService,
        private VarietyAnalyticsService $analyticsService,
    ) {}

    public function summary(): array
    {
        return [
            'total_varieties' => Variety::count(),
            'total_vegetables' => Vegetable::count(),
        ];
    }

    public function show(Variety $variety, int $year, int $month, VarietyViewerRole $role): Variety
    {
        $counts = $variety->postItems()
            ->ongoing()
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->whereNull('posts.deleted_at')
            ->selectRaw("
                SUM(CASE WHEN posts.type = 'supply' THEN 1 ELSE 0 END) as supply_count,
                SUM(CASE WHEN posts.type = 'demand' THEN 1 ELSE 0 END) as demand_count
            ")
            ->first();

        $variety->supply_count = (int) ($counts->supply_count ?? 0);
        $variety->demand_count = (int) ($counts->demand_count ?? 0);

        $variety->supply_municipalities = $this->resolveSupplyMunicipalities($variety->id);

        $monthlyActivity = $this->activityService->buildMonthlyActivity($variety->id);
        $variety->monthly_activity = $monthlyActivity;
        $variety->variety_calendar = $this->calendarService->buildForMonth($variety->id, $year, $month);
        $variety->analytics = $this->analyticsService->compute($monthlyActivity, $role);

        return $variety;
    }

    private function resolveSupplyMunicipalities(int $varietyId): array
    {
        return DB::table('post_items')
            ->join('posts', 'posts.id', '=', 'post_items.post_id')
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->join('farmer_profiles', 'users.id', '=', 'farmer_profiles.user_id')
            ->join('municipalities', 'farmer_profiles.municipality_id', '=', 'municipalities.id')
            ->where('post_items.variety_id', $varietyId)
            ->where('posts.type', PostType::Supply->value)
            ->where('post_items.status', PostItemStatus::Ongoing->value)
            ->whereNull('posts.deleted_at')
            ->whereNull('post_items.deleted_at')
            ->groupBy('municipalities.id', 'municipalities.name')
            ->select(
                'municipalities.name as municipality_name',
                DB::raw('SUM(post_items.quantity_kg) as total_kg'),
            )
            ->get()
            ->map(fn ($row) => [
                'name' => $row->municipality_name,
                'total_kg' => (float) $row->total_kg,
            ])
            ->sortByDesc('total_kg')
            ->values()
            ->toArray();
    }
}
