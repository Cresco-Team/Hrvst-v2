<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\FarmerSupply;
use App\Models\Profiles\FarmerProfile;
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

    public static function markers(?int $categoryId = null, ?int $varietyId = null): array
    {
        $query = FarmerProfile::query()
            ->approved()
            ->whereHas('supplies', fn (Builder $q) =>
                $q->whereHas('post', fn (Builder $p) => $p->ongoing())
            )
            ->with([
                'municipality',
                'barangay',
                'supplies' => fn (Builder $q) => $q
                    ->whereHas('post', fn (Builder $p) => $p->ongoing())
                    ->with('post.variety.vegetable.category'),
            ]);

        if ($categoryId) {
            $query->whereHas('supplies.post.variety.vegetable', fn (Builder $q) =>
                $q->where('category_id', $categoryId)
            );
        }

        if ($varietyId) {
            $query->whereHas('supplies.post', fn (Builder $q) =>
                $q->ongoing()->where('variety_id', $varietyId)
            );
        }

        return $query
            ->get()
            ->groupBy('barangay_id')
            ->map(function ($farmers) {
                /** @var \App\Models\Profiles\FarmerProfile $first */
                $first = $farmers->first();

                $allSupplies = $farmers->flatMap(fn ($farmer) => $farmer->supplies);

                $breakdown = $allSupplies
                    ->groupBy(fn ($supply) => $supply->post->variety->vegetable->name)
                    ->map(fn ($supplies, string $vegetable) => [
                        'vegetable'         => $vegetable,
                        'category'          => $supplies->first()->post->variety->vegetable->category->name,
                        'count'             => $supplies->count(),
                        'total_quantity_kg' => round(
                            $supplies->sum(fn ($s) => $s->post->quantity_kg),
                            2
                        ),
                        'varieties' => $supplies
                            ->pluck('post.variety.name')
                            ->unique()
                            ->values()
                            ->toArray(),
                    ])
                    ->values()
                    ->toArray();

                return [
                    'barangay_id'       => $first->barangay_id,
                    'barangay'          => $first->barangay->name,
                    'municipality_id'   => $first->municipality_id,
                    'municipality'      => $first->municipality->name,
                    'coordinates'       => [
                        'lat' => round((float) $farmers->avg('latitude'), 6),
                        'lng' => round((float) $farmers->avg('longitude'), 6),
                    ],
                    'supply_count'      => $allSupplies->count(),
                    'total_quantity_kg' => round(
                        $allSupplies->sum(fn ($s) => $s->post->quantity_kg),
                        2
                    ),
                    'supply_breakdown'  => $breakdown,
                ];
            })
            ->values()
            ->toArray();
    }

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
