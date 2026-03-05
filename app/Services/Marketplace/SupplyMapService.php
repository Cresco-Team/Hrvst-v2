<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\FarmerSupply;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;

class SupplyMapService
{
    public static function mapConfig(): array
    {
        return [
            'center' => [
                'lat' => 16.5712,
                'lng' => 120.6814,
            ],
            'zoom' => 10,
        ];
    }

    /**
     * Returns one marker per barangay that has at least one active supply.
     *
     * Querying from FarmerSupply avoids a constrained eager-load conflict with
     * FarmerSupply::$with = ['post']. Post is always loaded by the model; we
     * only need to extend the chain to variety.vegetable.category and bring in
     * the farmer's address relationships at the top level.
     *
     * Coordinates = arithmetic mean of all unique approved farmer positions in
     * the barangay. Zero farmer PII is exposed — no IDs, names, or contacts.
     */
    public static function markers(?int $categoryId = null, ?int $varietyId = null): array
    {
        $query = FarmerSupply::query()
            ->whereHas('post', fn (Builder $q) => $q->ongoing())
            ->whereHas('farmer', fn (Builder $q) => $q->approved())
            ->with([
                'farmer.municipality',
                'farmer.barangay',
                'post.variety.vegetable.category',
            ]);

        if ($categoryId) {
            $query->whereHas('post.variety.vegetable', fn (Builder $q) =>
                $q->where('category_id', $categoryId)
            );
        }

        if ($varietyId) {
            $query->whereHas('post', fn (Builder $q) =>
                $q->ongoing()->where('variety_id', $varietyId)
            );
        }

        return $query
            ->get()
            ->groupBy(fn (FarmerSupply $supply) => $supply->farmer->barangay_id)
            ->map(function ($supplies) {
                /** @var \App\Models\Marketplace\FarmerSupply $first */
                $first    = $supplies->first();
                $farmers  = $supplies->pluck('farmer')->unique('id');

                $breakdown = $supplies
                    ->groupBy(fn ($supply) => $supply->post->variety->vegetable->name)
                    ->map(fn ($grouped, string $vegetable) => [
                        'vegetable'         => $vegetable,
                        'category'          => $grouped->first()->post->variety->vegetable->category->name,
                        'count'             => $grouped->count(),
                        'total_quantity_kg' => round(
                            $grouped->sum(fn ($s) => $s->post->quantity_kg),
                            2
                        ),
                        'varieties' => $grouped
                            ->pluck('post.variety.name')
                            ->unique()
                            ->values()
                            ->toArray(),
                    ])
                    ->values()
                    ->toArray();

                return [
                    'barangay_id'       => $first->farmer->barangay_id,
                    'barangay'          => $first->farmer->barangay->name,
                    'municipality_id'   => $first->farmer->municipality_id,
                    'municipality'      => $first->farmer->municipality->name,
                    'coordinates'       => [
                        'lat' => round((float) $farmers->avg('latitude'), 6),
                        'lng' => round((float) $farmers->avg('longitude'), 6),
                    ],
                    'supply_count'      => $supplies->count(),
                    'total_quantity_kg' => round(
                        $supplies->sum(fn ($s) => $s->post->quantity_kg),
                        2
                    ),
                    'supply_breakdown'  => $breakdown,
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Filter options for the map sidebar.
     * Only returns categories/varieties that have active, non-expired supplies.
     */
    public static function filterOptions(): array
    {
        $categories = Category::whereHas(
            'vegetables.varieties.posts',
            fn (Builder $q) => $q->ongoing()->whereHasMorph(
                'postable',
                FarmerSupply::class,
                fn (Builder $q) => $q->where('expiration_date', '>=', now())
            )
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($category) => [
                'id'   => $category->id,
                'name' => $category->name,
            ])
            ->toArray();

        $varieties = Variety::whereHas(
            'posts',
            fn (Builder $q) => $q->ongoing()->whereHasMorph(
                'postable',
                FarmerSupply::class,
                fn (Builder $q) => $q->where('expiration_date', '>=', now())
            )
        )
            ->with('vegetable.category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($variety) => $variety->vegetable->category->name)
            ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                'id'   => $variety->id,
                'name' => $variety->vegetable->name . ' ' . $variety->name,
            ])->values()->toArray())
            ->toArray();

        return compact('categories', 'varieties');
    }
}
