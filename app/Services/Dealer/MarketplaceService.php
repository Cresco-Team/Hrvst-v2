<?php

namespace App\Services\Dealer;

use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerSupply::with([
            'media',
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->whereHas('post', fn ($q) => $q->ongoing());

        if (! empty($filters['category_id'])) {
            $query->whereHas('post.variety.vegetable', fn (Builder $q) =>
                $q->where('category_id', $filters['category_id'])
            );
        }
 
        if (! empty($filters['variety_id'])) {
            $query->whereHas('post', fn ($q) => $q->where('variety_id', $filters['variety_id']));
        }
 
        if (! empty($filters['municipality_id'])) {
            $query->whereHas('farmer', fn (Builder $q) =>
                $q->where('municipality_id', $filters['municipality_id'])
            );
        }

        return $query->orderBy('expiration_date', 'asc')->paginate($perPage);
    }

    public function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.posts', fn (Builder $q) => $q
            ->ongoing()
            ->whereHasMorph('postable', FarmerSupply::class, fn ($q) => $q
                ->where('expiration_date', '>=', now())
            )
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id'   => $category->id,
                'name' => $category->name,
            ])
            ->toArray();
    }
}
