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
            'posts' => fn ($q) => $q
                ->ongoing()
                ->with(['media', 'variety.media', 'variety.vegetable.category'])
                ->orderBy('scheduled_date', 'asc'),
        ])
            ->withCount([
                'posts as ongoing_supplies_count' => fn (Builder $q) => $q->ongoing(),
            ]);

        if ($search) {
            $query->whereHas('user', fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
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
            'posts' => fn ($q) => $q
                ->ongoing()
                ->with(['media', 'variety.media', 'variety.vegetable.category'])
                ->orderBy('scheduled_date', 'asc'),
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
            'posts' => fn ($q) => $q->with(['media', 'postItems.variety.vegetable.category']),
        ]);
    }
}
