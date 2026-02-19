<?php

namespace App\Services\Farmer;

use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DealerDemandService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerDemand::with([
            'dealer.user',
            'items.variety.vegetable.category',
            'items.variety.latestPrice',
        ])
            ->where('status', 'open')
            ->where('transaction_date', '>=', now());

        // Category filter
        if (!empty($filters['category_id'])) {
            $query->whereHas('items.variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $filters['category_id'])
            );
        }

        // Variety filter
        if (!empty($filters['variety_id'])) {
            $query->whereHas('items', fn(Builder $q) => 
                $q->where('variety_id', $filters['variety_id'])
            );
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        // Sort by transaction date (soonest first)
        $query->orderBy('transaction_date', 'asc');

        return $query->paginate($perPage)
            ->through(function ($request) {
                return [
                    'id' => $request->id,
                    'dealer' => [
                        'id' => $request->dealer->id,
                        'name' => $request->dealer->user->name,
                        'user_image' => $request->dealer->user->user_image,
                    ],
                    'transaction_date' => $request->transaction_date->format('M d, Y'),
                    'days_until_transaction' => now()->diffInDays($request->transaction_date, false),
                    'status' => $request->status,
                    'items' => $request->items->map(fn($item) => [
                        'variety' => [
                            'id' => $item->variety_id,
                            'name' => $item->variety->vegetable->name . ' ' . $item->variety->name,
                            'category' => $item->variety->vegetable->category->name,
                        ],
                        'quantity_kg' => (float) $item->quantity_kg,
                        'price_offered' => (float) $item->price_offered,
                        'price_flag' => self::calculatePriceFlag($item->price_offered, $item->variety->latestPrice),
                    ])->toArray(),
                    'total_quantity' => (float) $request->items->sum('quantity_kg'),
                    'created_at_human' => $request->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * Get detailed request with all items and reactions
     */
    public static function detailed(DealerDemand $request): array
    {
        $request->load([
            'dealer.user',
            'items.variety.vegetable.category',
            'items.variety.latestPrice',
            'reactions.user',
        ]);

        return [
            'id' => $request->id,
            'dealer' => [
                'id' => $request->dealer->id,
                'name' => $request->dealer->user->name,
                'phone_number' => $request->dealer->user->phone_number,
                'user_image' => $request->dealer->user->user_image,
            ],
            'transaction_date' => $request->transaction_date->format('M d, Y'),
            'days_until_transaction' => now()->diffInDays($request->transaction_date, false),
            'status' => $request->status,
            'items' => $request->items->map(fn($item) => [
                'id' => $item->id,
                'variety' => [
                    'id' => $item->variety_id,
                    'name' => $item->variety->vegetable->name . ' ' . $item->variety->name,
                    'category' => $item->variety->vegetable->category->name,
                    'image_url' => $item->variety->image_url,
                ],
                'quantity_kg' => (float) $item->quantity_kg,
                'price_offered' => (float) $item->price_offered,
                'price_flag' => self::calculatePriceFlag($item->price_offered, $item->variety->latestPrice),
                'market_price' => $item->variety->latestPrice ? [
                    'min' => (float) $item->variety->latestPrice->price_min,
                    'max' => (float) $item->variety->latestPrice->price_max,
                ] : null,
            ])->toArray(),
            'total_quantity' => (float) $request->items->sum('quantity_kg'),
            'created_at' => $request->created_at->format('M d, Y g:i A'),
            'created_at_human' => $request->created_at->diffForHumans(),
            'reaction_counts' => [
                'thumbs_up' => $request->reactions->where('reaction_type', 'thumbs_up')->count(),
                'thumbs_down' => $request->reactions->where('reaction_type', 'thumbs_down')->count(),
            ],
        ];
    }

    /**
     * Get category options that have open requests
     */
    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.dealerRequestItems.dealerRequest', function (Builder $q) {
            $q->where('status', 'open')
                ->where('transaction_date', '>=', now());
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
     * Calculate price flag
     */
    private static function calculatePriceFlag(float $priceOffered, ?object $marketPrice): string
    {
        if (!$marketPrice) {
            return 'unknown';
        }

        $marketMin = (float) $marketPrice->price_min;
        $marketMax = (float) $marketPrice->price_max;

        if ($priceOffered < $marketMin) {
            return 'cheap';
        }

        if ($priceOffered > $marketMax) {
            return 'high';
        }

        return 'fair';
    }
}
