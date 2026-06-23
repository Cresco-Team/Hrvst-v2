<?php

namespace App\Services\Marketplace;

use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Category;
use App\Models\Product\Variety;
use Illuminate\Database\Eloquent\Builder;

class SupplyMapService
{
    public function mapConfig(): array
    {
        return [
            'center' => ['lat' => 16.5712, 'lng' => 120.6814],
            'zoom' => 10,
        ];
    }

    public function markers(?int $categoryId = null, ?int $varietyId = null): array
    {
        $query = Post::supply()
            ->where('scheduled_date', '>=', now()->toDateString())
            ->whereHas('postItems', fn (Builder $q) => $q->ongoing())
            ->with([
                'farmerProfile.municipality',
                'farmerProfile.barangay',
                'postItems.variety.vegetable.category',
            ]);

        if ($categoryId) {
            $query->whereHas(
                'postItems.variety.vegetable',
                fn (Builder $q) => $q->where('category_id', $categoryId)
            );
        }

        if ($varietyId) {
            $query->whereHas(
                'postItems',
                fn (Builder $q) => $q->where('variety_id', $varietyId)->ongoing()
            );
        }

        return $query
            ->get()
            ->groupBy(fn (Post $post) => $post->farmerProfile->barangay_id)
            ->map(function ($posts) {
                /** @var Post $first */
                $first = $posts->first();
                $farmers = $posts->pluck('farmerProfile')->unique('id');

                $allItems = $posts->flatMap(fn (Post $post) => $post->postItems->where('status->value', 'ongoing'));

                $breakdown = $allItems
                    ->groupBy(fn (PostItem $item) => $item->variety->vegetable->name)
                    ->map(fn ($items, string $vegetable) => [
                        'vegetable' => $vegetable,
                        'category' => $items->first()->variety->vegetable->category->name,
                        'count' => $items->count(),
                        'total_quantity_kg' => round($items->sum('quantity_kg'), 2),
                        'varieties' => $items->pluck('variety.name')->unique()->values()->toArray(),
                    ])
                    ->values()
                    ->toArray();

                return [
                    'barangay_id' => $first->farmerProfile->barangay_id,
                    'barangay' => $first->farmerProfile->barangay->name,
                    'municipality_id' => $first->farmerProfile->municipality_id,
                    'municipality' => $first->farmerProfile->municipality->name,
                    'coordinates' => [
                        'lat' => round((float) $farmers->avg('latitude'), 6),
                        'lng' => round((float) $farmers->avg('longitude'), 6),
                    ],
                    'supply_count' => $posts->count(),
                    'total_quantity_kg' => round($allItems->sum('quantity_kg'), 2),
                    'supply_breakdown' => $breakdown,
                ];
            })
            ->values()
            ->toArray();
    }

    public function filterOptions(): array
    {
        $hasActiveSupply = fn (Builder $q) => $q->supply()
            ->where('scheduled_date', '>=', now()->toDateString())
            ->whereHas('postItems', fn (Builder $q2) => $q2->ongoing());

        $categories = Category::whereHas('vegetables.varieties.postItems.post', $hasActiveSupply)
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();

        $varieties = Variety::whereHas('postItems.post', $hasActiveSupply)
            ->with('vegetable.category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn ($variety) => $variety->vegetable->category->name)
            ->map(fn ($varieties) => $varieties->map(fn ($variety) => [
                'id' => $variety->id,
                'name' => $variety->vegetable->name.' '.$variety->name,
            ])->values()->toArray())
            ->toArray();

        return compact('categories', 'varieties');
    }
}
