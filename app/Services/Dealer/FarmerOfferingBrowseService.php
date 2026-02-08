<?php

namespace App\Services\Dealer;

use App\Models\Announcement\FarmerOffering;
use App\Models\Product\Category;
use App\Models\Address\Municipality;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FarmerOfferingBrowseService
{
    /**
     * Browse all active farmer offerings with filters
     */
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerOffering::with([
            'farmer.user',
            'farmer.municipality.province',
            'farmer.barangay',
            'variety.vegetable.category',
        ])
            ->where('status', 'active')
            ->where('expiration_date', '>=', now());

        // Search filter (variety or vegetable name)
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('variety', function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('vegetable', fn(Builder $vq) => 
                        $vq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $filters['category_id'])
            );
        }

        // Variety filter
        if (!empty($filters['variety_id'])) {
            $query->where('variety_id', $filters['variety_id']);
        }

        // Municipality filter (farmer location)
        if (!empty($filters['municipality_id'])) {
            $query->whereHas('farmer', fn(Builder $q) => 
                $q->where('municipality_id', $filters['municipality_id'])
            );
        }

        // Sort by expiration (soonest first by default)
        $query->orderBy('expiration_date', 'asc');

        return $query->paginate($perPage)
            ->through(function ($offering) {
                return [
                    'id' => $offering->id,
                    'farmer' => [
                        'id' => $offering->farmer->id,
                        'name' => $offering->farmer->user->name,
                        'location' => "{$offering->farmer->barangay->name}, {$offering->farmer->municipality->name}",
                        'municipality' => $offering->farmer->municipality->name,
                        'province' => $offering->farmer->municipality->province->name,
                    ],
                    'variety' => [
                        'id' => $offering->variety_id,
                        'name' => $offering->variety->vegetable->name . ' ' . $offering->variety->name,
                        'category' => $offering->variety->vegetable->category->name,
                    ],
                    'image_url' => $offering->image_url,
                    'quantity_kg' => (float) $offering->quantity_kg,
                    'price_asking' => (float) $offering->price_asking,
                    'expiration_date' => $offering->expiration_date->format('M d, Y'),
                    'days_until_expiration' => $offering->days_until_expiration,
                    'created_at_human' => $offering->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get detailed offering with comments and reactions
     */
    public static function detailed(FarmerOffering $offering): array
    {
        $offering->load([
            'farmer.user',
            'farmer.municipality.province',
            'farmer.barangay',
            'variety.vegetable.category',
            'reactions.user',
            'comments.user',
        ]);

        return [
            'id' => $offering->id,
            'farmer' => [
                'id' => $offering->farmer->id,
                'name' => $offering->farmer->user->name,
                'phone_number' => $offering->farmer->user->phone_number,
                'user_image' => $offering->farmer->user->user_image,
                'location' => [
                    'barangay' => $offering->farmer->barangay->name,
                    'municipality' => $offering->farmer->municipality->name,
                    'province' => $offering->farmer->municipality->province->name,
                    'full' => "{$offering->farmer->barangay->name}, {$offering->farmer->municipality->name}, {$offering->farmer->municipality->province->name}",
                ],
            ],
            'variety' => [
                'id' => $offering->variety_id,
                'name' => $offering->variety->vegetable->name . ' ' . $offering->variety->name,
                'category' => $offering->variety->vegetable->category->name,
            ],
            'image_url' => $offering->image_url,
            'quantity_kg' => (float) $offering->quantity_kg,
            'price_asking' => (float) $offering->price_asking,
            'expiration_date' => $offering->expiration_date->format('M d, Y'),
            'days_until_expiration' => $offering->days_until_expiration,
            'status' => $offering->status,
            'created_at' => $offering->created_at->format('M d, Y g:i A'),
            'created_at_human' => $offering->created_at->diffForHumans(),
            'reaction_counts' => $offering->reactions
                ->groupBy('reaction_type')
                ->map(fn($group) => $group->count())
                ->toArray(),
            'comment_count' => $offering->comments->count(),
        ];
    }

    /**
     * Get category options that have active offerings
     */
    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.offerings', function (Builder $q) {
            $q->where('status', 'active')
                ->where('expiration_date', '>=', now());
        })
            ->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->toArray();
    }

    /**
     * Get municipality options that have active offerings
     */
    public static function municipalityOptions(): array
    {
        return Municipality::whereHas('farmers.offerings', function (Builder $q) {
            $q->where('status', 'active')
                ->where('expiration_date', '>=', now());
        })
            ->with('province')
            ->orderBy('name')
            ->get()
            ->map(fn($municipality) => [
                'id' => $municipality->id,
                'name' => $municipality->name,
                'province' => $municipality->province->name,
                'label' => "{$municipality->name}, {$municipality->province->name}",
            ])
            ->toArray();
    }
}
