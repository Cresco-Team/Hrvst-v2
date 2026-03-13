<?php

namespace App\Services\Admin;

use App\Models\Marketplace\DealerDemand;
use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DealerService
{
    public function summary(): array
    {
        $newDealersThisMonth = DealerProfile::approved()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
        $newDemandsThisMonth = DealerDemand::where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'total_dealers' => DealerProfile::approved()->count(),
            'new_dealers_this_month' => $newDealersThisMonth,
            'total_demands' => DealerDemand::count(),
            'new_demands_this_month' => $newDemandsThisMonth,
        ];
    }

    public function paginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = DealerProfile::with([
            'user.media',
            'demands' => fn ($query) => $query
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('transaction_date', 'asc'),
        ])
            ->approved();

        if ($search) {
            $query->whereHas('user', function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->through(function (DealerProfile $dealer) {
                $dealer->document_url = route('admin.dealers.document', $dealer->id);
                return $dealer;
            });
    }

    public function details(DealerProfile $dealer): DealerProfile
    {
        return $dealer->load([
            'user.media',
            'demands' => fn ($q) => $q
                ->whereHas('post', fn ($q) => $q->ongoing())
                ->with(['post.variety.media', 'post.variety.vegetable.category'])
                ->orderBy('transaction_date', 'asc'),
        ]);
    }

    public function show(DealerProfile $dealer): DealerProfile
    {
        return $dealer->load([
            'user.media',
            'demands',
            'demands.post.variety.media',
            'demands.post.variety.vegetable.category',
        ]);
    }

    public function pending(): Collection
    {
        $dealers = DealerProfile::with(['user.media'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get();
        
        $dealers->each(fn (DealerProfile $dealer) =>
            $dealer->document_url = route('admin.dealers.document', $dealer->id)
        );

        return $dealers;
    }
}
