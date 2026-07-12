<?php

namespace App\Services\Admin;

use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;
use App\Services\Shared\PostItemInsightsService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DealerService
{
    public function __construct(
        private readonly PostItemInsightsService $insights,
    ) {}

    public function summary(): array
    {
        return [
            'total_dealers' => DealerProfile::count(),
            'new_dealers_this_month' => DealerProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'total_demands' => Post::demand()->count(),
            'new_demands_this_month' => Post::demand()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    public function paginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = DealerProfile::with(['user.media'])
            ->withCount([
                'demandItems as ongoing_demands_count' => fn (Builder $q) => $q
                    ->ongoing(),
            ]);

        if ($search) {
            $query->whereHas('user', function (Builder $q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('phone_number', 'ilike', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function details(DealerProfile $dealer): DealerProfile
    {
        return $dealer->load([
            'user.media',
            'posts' => fn ($q) => $q
                ->demand()
                ->whereDate('scheduled_date', today())
                ->with(['postItems' => fn ($q) => $q->ongoing()])
                ->with(['postItems.vegetable']),
        ]);
    }

    public function show(DealerProfile $dealer, bool $hasAnalyticsAccess): DealerProfile
    {
        $dealer->load([
            'user.media',
            'demandItems' => fn ($q) => $q
                ->with(['vegetable.category']),
        ]);

        if ($hasAnalyticsAccess) {
            $dealer->insights = $this->insights->compute($dealer->user_id, PostType::Supply);
        }

        $dealer->insights = $this->insights->compute($dealer->user_id, PostType::Demand);

        return $dealer;
    }
}
