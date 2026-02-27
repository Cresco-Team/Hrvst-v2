<?php

namespace App\Services\Dealer;

use App\Models\Product\Category;
use App\Models\Marketplace\FarmerSupply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerSupply::with([
            'farmer.user',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->whereHas('post', fn ($q) => $q->ongoing());

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('post.variety', function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('vegetable', fn(Builder $vq) => 
                        $vq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('post.variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $filters['category_id'])
            );
        }

        if (!empty($filters['variety_id'])) {
            $query->whereHas('post', fn ($q) => $q
                ->where('variety_id', $filters['variety_id']));
        }

        return $query->orderBy('expiration_date', 'asc')
            ->paginate($perPage)
            ->through(function (FarmerSupply $supply) {
                return [
                    'id' => $supply->id,
                    'farmer' => [
                        'id' => $supply->farmer->id,
                        'name' => $supply->farmer->user->name,
                        'image_url' => $supply->farmer?->image_url,
                    ],
                    'variety' => [
                        'id' => $supply->post->variety_id,
                        'name' => $supply->post->variety->name,
                        'vegetable' => $supply->post->variety->vegetable->name,
                        'image_url' => $supply->post->variety->image_url,
                    ],
                    'title' => $supply->post->title,
                    'image_url' => $supply->image_url,
                    'quantity_kg' => (float) $supply->post->quantity_kg,
                    'offered_price' => (float) $supply->post->offered_price,
                    'price_flag' => $supply->post->price_flag,
                    'expiration_date' => $supply->expiration_date->format('M d, Y'),
                    'days_until_expiration' => $supply->days_until_expiration,
                    'status' => $supply->post->status,
                    'created_at_human' => $supply->created_at->diffForHumans(),
                ];
            });
    }

    public static function detailed(FarmerSupply $supply): array
    {
        $supply->load([
            'farmer.user',
            'farmer.barangay',
            'variety.vegetable.category',
            'reactions.user',
            'comments.user',
        ]);

        return [
            'id' => $supply->id,
            'farmer' => [
                'id' => $supply->farmer->id,
                'name' => $supply->farmer->user->name,
                'phone_number' => $supply->farmer->user->phone_number,
                'user_image' => $supply->farmer->user->user_image,
            ],
            'variety' => [
                'id' => $supply->variety_id,
                'name' => $supply->variety->vegetable->name . ' ' . $supply->variety->name,
                'category' => $supply->variety->vegetable->category->name,
            ],
            'image_url' => $supply->image_url,
            'quantity_kg' => (float) $supply->quantity_kg,
            'price_asking' => (float) $supply->asking_price,
            'expiration_date' => $supply->expiration_date->format('M d, Y'),
            'days_until_expiration' => $supply->days_until_expiration,
            'status' => $supply->status,
            'created_at' => $supply->created_at->format('M d, Y g:i A'),
            'created_at_human' => $supply->created_at->diffForHumans(),
        ];
    }

    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.posts', fn (Builder $q) => $q
            ->ongoing()
            ->whereHasMorph('postable', FarmerSupply::class, fn ($q) => $q
                ->where('expiration_date', '>=', now())
            )
        )->orderBy('name')
        ->get()
        ->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->name,
        ])->toArray();
    }
}
