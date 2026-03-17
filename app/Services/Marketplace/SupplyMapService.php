<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Post;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;

class SupplyMapService
{
    public function mapConfig(): array
    {
        return [
            'center' => ['lat' => 16.5712, 'lng' => 120.6814],
            'zoom'   => 10,
        ];
    }

    public function markers(?int $categoryId = null, ?int $varietyId = null): array
    {
        $query = Post::supply()
            ->ongoing()
            ->whereHas('farmerProfile', fn (Builder $q) => $q->where('is_approved', true))
            ->with([
                'farmerProfile.municipality',
                'farmerProfile.barangay',
                'variety.vegetable.category',
            ]);

        if ($categoryId) {
            $query->whereHas('variety.vegetable', fn (Builder $q) =>
                $q->where('category_id', $categoryId)
            );
        }

        if ($varietyId) {
            $query->where('variety_id', $varietyId);
        }

        return $query
            ->get()
            ->groupBy(fn (Post $post) => $post->farmerProfile->barangay_id)
            ->map(function ($posts) {
                /** @var Post $first */
                $first   = $posts->first();
                $farmers = $posts->pluck('farmerProfile')->unique('id');

                $breakdown = $posts
                    ->groupBy(fn (Post $post) => $post->variety->vegetable->name)
                    ->map(fn ($grouped, string $vegetable) => [
                        'vegetable'         => $vegetable,
                        'category'          => $grouped->first()->variety->vegetable->category->name,
                        'count'             => $grouped->count(),
                        'total_quantity_kg' => round($grouped->sum('quantity_kg'), 2),
                        'varieties'         => $grouped->pluck('variety.name')->unique()->values()->toArray(),
                    ])
                    ->values()
                    ->toArray();

                return [
                    'barangay_id'       => $first->farmerProfile->barangay_id,
                    'barangay'          => $first->farmerProfile->barangay->name,
                    'municipality_id'   => $first->farmerProfile->municipality_id,
                    'municipality'      => $first->farmerProfile->municipality->name,
                    'coordinates'       => [
                        'lat' => round((float) $farmers->avg('latitude'), 6),
                        'lng' => round((float) $farmers->avg('longitude'), 6),
                    ],
                    'supply_count'      => $posts->count(),
                    'total_quantity_kg' => round($posts->sum('quantity_kg'), 2),
                    'supply_breakdown'  => $breakdown,
                ];
            })
            ->values()
            ->toArray();
    }

    public function filterOptions(): array
    {
        $categories = Category::whereHas(
            'vegetables.varieties.posts',
            fn (Builder $q) => $q->ongoing()->supply()->where('scheduled_date', '>=', now())
        )
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();

        $varieties = Variety::whereHas(
            'posts',
            fn (Builder $q) => $q->ongoing()->supply()->where('scheduled_date', '>=', now())
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
