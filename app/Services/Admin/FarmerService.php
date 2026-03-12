<?php

namespace App\Services\Admin;

use App\Models\Marketplace\FarmerSupply;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class FarmerService
{
    public function summary(): array
    {
        $newFarmerThisMonth = FarmerProfile::approved()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newSuppliesThisMonth = FarmerSupply::where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total_farmers'           => FarmerProfile::approved()->count(),
            'new_farmers_this_month'  => $newFarmerThisMonth,
            'total_supplies'          => FarmerSupply::count(),
            'new_supplies_this_month' => $newSuppliesThisMonth,
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
            'supplies' => fn ($query) => $query
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['media', 'post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('expiration_date', 'asc'),
        ])
        ->withCount([
            'supplies as ongoing_supplies_count' => fn (Builder $q) => $q
                ->whereHas('post', fn ($q) => $q->ongoing()),
        ])
            ->approved();

        if ($search) {
            $query->whereHas('user', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function details(farmerProfile $farmer): FarmerProfile
    {
        return $farmer->load([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
            'supplies' => fn ($q) => $q
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['media', 'post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('expiration_date', 'asc'),
        ]);
    }

    public static function show(FarmerProfile $farmer): FarmerProfile
    {
        return $farmer->load([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
            'supplies.media',
            'supplies.post.variety.media',
            'supplies.post.variety.vegetable.category',
        ]);
    }

    public function pending(): Collection
    {
        return FarmerProfile::with([
            'user.media',
            'media',
            'province',
            'municipality',
            'barangay',
        ])
        ->pending()
        ->orderBy('created_at', 'asc')
        ->get();
    }
}
