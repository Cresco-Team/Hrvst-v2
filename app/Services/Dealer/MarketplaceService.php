<?php

namespace App\Services\Dealer;

use App\FarmerOfferingStatus;
use App\Models\Product\Category;
use App\Models\Marketplace\FarmerOffering;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = FarmerOffering::with([
            'farmer.user',
            'farmer.municipality.province',
            'farmer.barangay',
            'variety.vegetable.category',
        ])->where('status', FarmerOfferingStatus::Available);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('variety', function (Builder $q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhereHas('vegetable', fn(Builder $vq) => 
                        $vq->where('name', 'LIKE', "%{$search}%")
                    );
            });
        }

        if (!empty($filters['category_id'])) {
            $query->whereHas('variety.vegetable', fn(Builder $q) => 
                $q->where('category_id', $filters['category_id'])
            );
        }

        if (!empty($filters['variety_id'])) {
            $query->where('variety_id', $filters['variety_id']);
        }

        $query->orderBy('expiration_date', 'asc');

        return $query->paginate($perPage)
            ->through(function ($offering) {
                return [
                    'id' => $offering->id,
                    'farmer' => [
                        'id' => $offering->farmer->id,
                        'name' => $offering->farmer->user->name,
                        'farm_url' => $offering->farmer?->farm_url,
                    ],
                    'variety' => [
                        'id' => $offering->variety_id,
                        'name' => $offering->variety->name,
                        'vegetable' => $offering->variety->vegetable->name,
                    ],
                    'image_url' => $offering->image_url,
                    'weight_kg' => (float) $offering->weight_kg,
                    'asking_price' => (float) $offering->asking_price,
                    'expiration_date' => $offering->expiration_date->format('M d, Y'),
                    'days_until_expiration' => $offering->days_until_expiration,
                    'created_at_human' => $offering->created_at->diffForHumans(),
                ];
            });
    }

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
            'price_asking' => (float) $offering->asking_price,
            'expiration_date' => $offering->expiration_date->format('M d, Y'),
            'days_until_expiration' => $offering->days_until_expiration,
            'status' => $offering->status,
            'created_at' => $offering->created_at->format('M d, Y g:i A'),
            'created_at_human' => $offering->created_at->diffForHumans(),
        ];
    }

    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.offerings', function (Builder $q) {
            $q->available();
        })->orderBy('name')
        ->get()
        ->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->name,
        ])->toArray();
    }
}
