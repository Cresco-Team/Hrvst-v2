<?php

namespace App\Services\Farmer;

use App\DealerPriceFlag;
use App\Enums\DealerDemandStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = DealerDemand::with([
            'dealer.user',
            'variety.vegetable.category',
            'variety.latestPrice',
        ])->where('status', DealerDemandStatus::Open)
        ->where('transaction_date', '>=', now());

        if (!empty($filters['category_id'])) {
            $query->whereHas('variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $filters['category_id'])
            );
        }

        if (!empty($filters['variety_id'])) {
            $query->where('variety_id', $filters['variety_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        $query->orderBy('transaction_date', 'asc');

        return $query->paginate($perPage)
            ->through(function ($demand) {
                return [
                    'id' => $demand->id,
                    'dealer' => [
                        'id' => $demand->dealer->id,
                        'name' => $demand->dealer->user->name,
                        'phone_number' => $demand->dealer->user->phone_number,
                        'image_path' => $demand->dealer->user->image_path,
                    ],
                    'transaction_date' => $demand->transaction_date->format('M d, Y'),
                    'days_until_transaction' => now()->diffInDays($demand->transaction_date, false),
                    'status' => $demand->status,
                    'variety' => [
                        'id' => $demand->variety->id,
                        'name' => $demand->variety->name,
                        'vegetable' => $demand->variety->vegetable->name,
                        'image_url' => $demand->variety->image_url,
                    ],
                    'quantity_kg' => (float) $demand->quantity_kg,
                    'price_offered' => (float) $demand->price_offered,
                    'price_flag' => self::calculatePriceFlag($demand->price_offered, $demand->variety->latest_price),
                    'created_at_human' => $demand->created_at->diffForHumans(),
                ];
            });
    }

    public static function detailed(DealerDemand $demand): array
    {
        $demand->load([
            'dealer.user',
            'variety.vegetable.category',
            'variety.latestPrice',
            'post.reactions.user',
        ]);

        return [
            'id' => $demand->id,
            'dealer' => [
                'id' => $demand->dealer->id,
                'name' => $demand->dealer->user->name,
                'phone_number' => $demand->dealer->user->phone_number,
                'image_path' => $demand->dealer->user->image_path,
            ],
            'transaction_date' => $demand->transaction_date->format('M d, Y'),
            'days_until_transaction' => now()->diffInDays($demand->transaction_date, false),
            'status' => $demand->status,
            'variety' => [
                'id' => $demand->variety_id,
                'name' => $demand->variety->name,
                'vegetable' => $demand->variety->vegetable->name,
                'image_url' => $demand->variety->image_url,
            ],
            'quantity_kg' => (float) $demand->quantity_kg,
            'price_offered' => (float) $demand->price_offered,
            'price_flag' => self::calculatePriceFlag($demand->price_offered, $demand->variety->latestPrice),
            'market_price' => [
                'min' => (float) $demand->variety->latestPrice->price_min,
                'max' => (float) $demand->variety->latestPrice->price_max,
            ],
            'created_at' => $demand->created_at->format('M d, Y g:i A'),
            'created_at_human' => $demand->created_at->diffForHumans(),
        ];
    }

    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.demands', function (Builder $q) {
            $q->where('status', DealerDemandStatus::Open)
                ->where('transaction_date', '>=', now());
        })->orderBy('name')
            ->get()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])->toArray();
    }

    private static function calculatePriceFlag(float $priceOffered, ?object $latestPrice): DealerPriceFlag
    {

        $marketMin = (float) $latestPrice?->price_min;
        $marketMax = (float) $latestPrice?->price_max;

        if ($priceOffered < $marketMin) return DealerPriceFlag::Low;

        if ($priceOffered > $marketMax) return DealerPriceFlag::Premium;

        return DealerPriceFlag::Fair;
    }
}
