<?php

namespace App\Services\Admin;

use App\Models\Marketplace\Post;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FarmerService
{
    public function summary(): array
    {
        return [
            'total_farmers' => FarmerProfile::count(),
            'new_farmers_this_month' => FarmerProfile::where('created_at', '>=', now()->startOfMonth())->count(),
            'total_supplies' => Post::supply()->count(),
            'new_supplies_this_month' => Post::supply()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    public function paginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = FarmerProfile::with([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
        ])
            // Count harvested supply posts that have at least one ongoing PostItem —
            // this is the admin's "active deliveries" metric per farmer.
            ->withCount([
                'posts as ongoing_supplies_count' => fn (Builder $q) => $q
                    ->supply()
                    ->harvested()
                    ->whereHas('postItems', fn (Builder $q) => $q->ongoing()),
            ]);

        if ($search) {
            $query->whereHas(
                'user', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
            );
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function details(FarmerProfile $farmer): FarmerProfile
    {
        return $farmer->load([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
            // Load posts + their ongoing PostItems for the sidebar summary
            'posts' => fn ($q) => $q
                ->supply()
                ->harvested()
                ->with([
                    'postItems' => fn ($q) => $q
                        ->ongoing()
                        ->with(['variety.media', 'variety.vegetable.category']),
                ]),
        ]);
    }

    public function show(FarmerProfile $farmer): FarmerProfile
    {
        return $farmer->load([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
            // Growing posts (pre-harvest)
            'posts' => fn ($q) => $q->supply()->growing(),
            // All PostItems across all supply posts for the tabbed status view
            'supplyItems' => fn ($q) => $q
                ->with(['variety.vegetable.category', 'variety.media', 'post']),
        ]);
    }
}
