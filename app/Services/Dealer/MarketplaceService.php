<?php

namespace App\Services\Dealer;

use App\Models\Announcement\FarmerOffering;
use App\Models\Product\Category;
use App\Models\Address\Municipality;
use App\Models\Product\Planting;
use App\PlantingStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceService
{
    public static function paginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Planting::with([
            'farmer.user',
            'farmer.municipality.province',
            'farmer.barangay',
            'variety.vegetable.category',
        ])->where('status', PlantingStatus::Available);

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

        if (!empty($filters['municipality_id'])) {
            $query->whereHas('farmer', fn(Builder $q) => 
                $q->where('municipality_id', $filters['municipality_id'])
            );
        }

        $query->orderBy('expiration_date', 'asc');

        return $query->paginate($perPage)
            ->through(function ($planting) {
                return [
                    'id' => $planting->id,
                    'farmer' => [
                        'id' => $planting->farmer->id,
                        'name' => $planting->farmer->user->name,
                        'location' => "{$planting->farmer->barangay->name}, {$planting->farmer->municipality->name}, {$planting->farmer->province->name}",
                        'municipality' => $planting->farmer->municipality->name,
                        'province' => $planting->farmer->municipality->province->name,
                    ],
                    'variety' => [
                        'id' => $planting->variety_id,
                        'name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                        'category' => $planting->variety->vegetable->category->name,
                    ],
                    'image_url' => $planting->image_url,
                    'quantity_kg' => (float) $planting->weight_kg,
                    'asking_price' => (float) $planting->asking_price,
                    'expiration_date' => $planting->expiration_date->format('M d, Y'),
                    'days_until_expiration' => $planting->days_until_expiration,
                    'created_at_human' => $planting->created_at->diffForHumans(),
                ];
            });
    }

    public static function detailed(Planting $planting): array
    {
        $planting->load([
            'farmer.user',
            'farmer.municipality.province',
            'farmer.barangay',
            'variety.vegetable.category',
            'reactions.user',
            'comments.user',
        ]);

        return [
            'id' => $planting->id,
            'farmer' => [
                'id' => $planting->farmer->id,
                'name' => $planting->farmer->user->name,
                'phone_number' => $planting->farmer->user->phone_number,
                'user_image' => $planting->farmer->user->user_image,
                'location' => [
                    'barangay' => $planting->farmer->barangay->name,
                    'municipality' => $planting->farmer->municipality->name,
                    'province' => $planting->farmer->municipality->province->name,
                    'full' => "{$planting->farmer->barangay->name}, {$planting->farmer->municipality->name}, {$planting->farmer->municipality->province->name}",
                ],
            ],
            'variety' => [
                'id' => $planting->variety_id,
                'name' => $planting->variety->vegetable->name . ' ' . $planting->variety->name,
                'category' => $planting->variety->vegetable->category->name,
            ],
            'image_url' => $planting->image_url,
            'quantity_kg' => (float) $planting->quantity_kg,
            'price_asking' => (float) $planting->asking_price,
            'expiration_date' => $planting->expiration_date->format('M d, Y'),
            'days_until_expiration' => $planting->days_until_expiration,
            'status' => $planting->status,
            'created_at' => $planting->created_at->format('M d, Y g:i A'),
            'created_at_human' => $planting->created_at->diffForHumans(),
            'reaction_counts' => $planting->reaction_counts,
            'comment_count' => $planting->comments->count(),
        ];
    }

    public static function categoryOptions(): array
    {
        return Category::whereHas('vegetables.varieties.plantings', function (Builder $q) {
            $q->available();
        })->orderBy('name')
        ->get()
        ->map(fn($category) => [
            'id' => $category->id,
            'name' => $category->name,
        ])->toArray();
    }

    public static function municipalityOptions(): array
    {
        return Municipality::whereHas('farmers.plantings', function (Builder $q) {
            $q->available();
        })->with('province')
        ->orderBy('name')
        ->get()
        ->map(fn($municipality) => [
            'id' => $municipality->id,
            'name' => $municipality->name,
            'province' => $municipality->province->name,
            'label' => "{$municipality->name}, {$municipality->province->name}",
        ])->toArray();
    }
}
