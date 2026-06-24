<?php

namespace App\Services\Product;

use App\Enums\Analytics\VarietyViewerRole;
use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Http\Resources\Product\VegetableResource;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use App\Models\Product\Vegetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class VarietyService
{
    public function __construct(
        private VegetableActivityService $activityService,
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

    public function table(
        ?string $search = null,
        ?int $categoryId = null,
        ?int $userId = null,
        ?int $perPage = null,
    ): array {
        $query = Vegetable::with([
            'category',
            'varieties' => function (HasMany $q) use ($search): void {
                $q->withCount([
                    'postItems as supply_count' => fn (Builder $q) => $q->ongoing()->whereHas(
                        'post', fn (Builder $p) => $p->supply()
                    ),
                    'postItems as demand_count' => fn (Builder $q) => $q->ongoing()->whereHas(
                        'post', fn (Builder $p) => $p->demand()
                    ),
                ])
                    ->orderBy('name');

                if ($search) {
                    $q->where('name', 'like', "%{$search}%");
                }
            },
        ])
            ->when($categoryId, fn (Builder $q) => $q->where('category_id', $categoryId))
            ->withCount('varieties')
            ->orderBy('name');

        if ($search) {
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('varieties', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($perPage !== null) {
            $paginator = $query->whereHas('varieties')->paginate($perPage)->withQueryString();

            return VegetableResource::collection($paginator)->response()->getData(true);
        }

        return VegetableResource::collection($query->get())->resolve();
    }

    public function forCatalog(int $perPage = 20, ?string $search = null, ?int $categoryId = null): LengthAwarePaginator
    {
        return Variety::with(['vegetable.category'])
            ->withCount([
                'postItems as supply_count' => fn (Builder $q) => $q->ongoing()->whereHas(
                    'post', fn (Builder $p) => $p->supply()
                ),
                'postItems as demand_count' => fn (Builder $q) => $q->ongoing()->whereHas(
                    'post', fn (Builder $p) => $p->demand()
                ),
            ])
            ->when($search, fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhereHas('vegetable', fn (Builder $q) => $q->where('name', 'like', "%{$search}%"))
            )
            ->when($categoryId, fn (Builder $q) => $q->whereHas(
                'vegetable', fn ($q) => $q->where('category_id', $categoryId)
            ))
            ->orderBy('name')
            ->paginate($perPage);
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

        $monthlyActivity = $this->activityService->buildMonthlyActivity($variety->vegetable_id);
        $variety->monthly_activity = $monthlyActivity;
        $variety->variety_calendar = $this->calendarService->buildForMonth($variety->id, $year, $month);
        $variety->analytics = $this->analyticsService->compute($monthlyActivity, $role);

        return $variety;
    }

    public function vegetableOptions(): array
    {
        return cache()->remember('vegetable_options', 3600, function () {
            return Vegetable::with('category')
                ->get()
                ->groupBy('category.name')
                ->map(fn ($vegetables) => $vegetables->pluck('name', 'id')->toArray())
                ->toArray();
        });
    }

    public function categoryOptions(): array
    {
        return cache()->remember('category_options', 3600, function () {
            return Category::orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        });
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
