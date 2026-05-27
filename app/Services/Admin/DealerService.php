<?php

namespace App\Services\Admin;

use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DealerService
{
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
        $query = DealerProfile::with(['user.media', 'demandItems'])
            ->withCount([
                'demandItems as ongoing_demands_count' => fn (Builder $q) => $q
                    ->ongoing(),
            ]);

        if ($search) {
            $query->whereHas('user', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function details(DealerProfile $dealer): DealerProfile
    {
        return $dealer->load([
            'user.media',
            'demandItems',
        ]);
    }

    public function show(DealerProfile $dealer): DealerProfile
    {
        return $dealer->load([
            'user.media',
            'demandItems' => fn ($q) => $q
                ->with(['variety.vegetable.category']),
        ]);
    }
}
