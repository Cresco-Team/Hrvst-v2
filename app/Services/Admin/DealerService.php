<?php

namespace App\Services\Admin;

use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DealerService
{
    public function summary(): array
    {
        return [
            'total_dealers' => DealerProfile::approved()->count(),
            'new_dealers_this_month' => DealerProfile::approved()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
            'total_demands' => Post::demand()->count(),
            'new_demands_this_month' => Post::demand()
                ->where('created_at', '>=', now()->startOfMonth())
                ->count(),
        ];
    }

    public function paginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        $query = DealerProfile::with([
            'user.media',
            'posts' => fn ($q) => $q
                ->ongoing()
                ->with(['variety.media', 'variety.vegetable.category'])
                ->orderBy('scheduled_date', 'asc'),
        ])
            ->withCount([
                'posts as ongoing_demands_count' => fn (Builder $q) => $q->ongoing(),
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
            'posts' => fn ($q) => $q
                ->ongoing()
                ->with(['variety.media', 'variety.vegetable.category'])
                ->orderBy('scheduled_date', 'asc'),
        ]);
    }

    public function show(DealerProfile $dealer): DealerProfile
    {
        $dealer->document_url = route('admin.dealers.document', $dealer->id);

        return $dealer->load([
            'user.media',
            'posts',
            'posts.variety.media',
            'posts.variety.vegetable.category',
        ]);
    }

    public function pending(): Collection
    {
        $dealers = DealerProfile::with(['user.media'])
            ->pending()
            ->orderBy('created_at', 'asc')
            ->get();

        $dealers->each(fn (DealerProfile $dealer) => $dealer->document_url = route('admin.dealers.document', $dealer->id)
        );

        return $dealers;
    }
}
