<?php

namespace App\Services\Dealer;

use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerSupply::with([
            'farmer.user.media',
            'media',
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.variety.latestPrice',
        ])->whereHas('post', fn ($q) => $q->ongoing());

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('post.variety', function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('vegetable', fn (Builder $vq) =>
                        $vq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        if (! empty($filters['category_id'])) {
            $query->whereHas('post.variety.vegetable', fn (Builder $q) =>
                $q->where('category_id', $filters['category_id'])
            );
        }

        if (! empty($filters['variety_id'])) {
            $query->whereHas('post', fn ($q) => $q->where('variety_id', $filters['variety_id']));
        }

        return $query->orderBy('expiration_date', 'asc')
            ->paginate($perPage)
            ->through(fn (FarmerSupply $supply) => [
                'id'     => $supply->id,
                'farmer' => [
                    'id'         => $supply->farmer->id,
                    'name'       => $supply->farmer->user->name,
                    'avatar_url' => $supply->farmer->user->getFirstMediaUrl('avatar'), // was: $supply->farmer?->image_url (never existed on FarmerProfile)
                ],
                'variety' => [
                    'id'        => $supply->post->variety_id,
                    'name'      => $supply->post->variety->name,
                    'vegetable' => $supply->post->variety->vegetable->name,
                    'image_url' => $supply->post->variety->getFirstMediaUrl('variety_image'),
                ],
                'title'                 => $supply->post->title,
                'image_url'             => $supply->getFirstMediaUrl('supply_image'),
                'quantity_kg'           => (float) $supply->post->quantity_kg,
                'offered_price'         => (float) $supply->post->offered_price,
                'price_flag'            => $supply->post->price_flag,
                'expiration_date'       => $supply->expiration_date->format('M d, Y'),
                'days_until_expiration' => $supply->days_until_expiration,
                'status'                => $supply->post->status,
                'created_at_human'      => $supply->created_at->diffForHumans(),
            ]);
    }

    public static function detailed(FarmerSupply $supply): array
    {
        // All variety/status/quantity data lives on Post, not on FarmerSupply directly.
        // Previous code accessed $supply->variety_id, $supply->variety->...,
        // $supply->quantity_kg, $supply->asking_price, $supply->status — all wrong.
        $supply->load([
            'farmer.user.media',
            'farmer.barangay',
            'media',
            'post.variety.media',
            'post.variety.vegetable.category',
            'post.reactions.user',
            'post.comments.user',
        ]);

        return [
            'id'     => $supply->id,
            'farmer' => [
                'id'           => $supply->farmer->id,
                'name'         => $supply->farmer->user->name,
                'phone_number' => $supply->farmer->user->phone_number,
                'avatar_url'   => $supply->farmer->user->getFirstMediaUrl('avatar'), // was: user_image (non-existent accessor)
            ],
            'variety' => [
                'id'       => $supply->post->variety_id,               // was: $supply->variety_id (bug)
                'name'     => $supply->post->variety->vegetable->name.' '.$supply->post->variety->name, // was: $supply->variety->... (bug)
                'category' => $supply->post->variety->vegetable->category->name,
            ],
            'image_url'             => $supply->getFirstMediaUrl('supply_image'),
            'quantity_kg'           => (float) $supply->post->quantity_kg,    // was: $supply->quantity_kg (bug)
            'offered_price'         => (float) $supply->post->offered_price,  // was: $supply->asking_price (wrong key + bug)
            'price_flag'            => $supply->post->price_flag,
            'expiration_date'       => $supply->expiration_date->format('M d, Y'),
            'days_until_expiration' => $supply->days_until_expiration,
            'status'                => $supply->post->status,                 // was: $supply->status (bug)
            'created_at'            => $supply->created_at->format('M d, Y g:i A'),
            'created_at_human'      => $supply->created_at->diffForHumans(),
        ];
    }

    public static function categoryOptions(): array
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
