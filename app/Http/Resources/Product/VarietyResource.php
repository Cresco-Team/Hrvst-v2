<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VarietyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /* Always present */
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->getFirstMediaUrl('variety_image'),
            'hearts_count' => $this->hearts_count,
            'is_hearted' => (bool) ($this->is_hearted ?? false),

            /* with('vegetable.category') */
            'vegetable' => $this->whenLoaded('vegetable', function () {
                return [
                    'id' => $this->vegetable->id,
                    'name' => $this->vegetable->name,
                    'category' => $this->vegetable->relationLoaded('category')
                        ? [
                            'id' => $this->vegetable->category->id,
                            'name' => $this->vegetable->category->name,
                        ]
                        : null,
                ];
            }),

            /* with('latestPrice) */
            'latest_price' => $this->whenLoaded('latestPrice', function () {
                return $this->latestPrice
                    ? new PriceHistoryResource($this->latestPrice)
                    : null;
            }),

            /* Computed when latestPrice is loaded */
            'price_updated_human' => $this->whenLoaded('latestPrice', fn () => $this->latestPrice?->recorded_at->diffForHumans()
            ),
            'price_updated_date' => $this->whenLoaded('latestPrice', fn () => $this->latestPrice?->recorded_at->format('M d, Y')
            ),

            /* with('lastTwoPrices) */
            'price_trend' => $this->whenLoaded('lastTwoPrices', function () {
                $prices = $this->lastTwoPrices;

                if ($prices->count() < 2) {
                    return null;
                }

                $latest = (float) $prices->first()->price_max;
                $previous = (float) $prices->last()->price_max;

                return match (true) {
                    $latest > $previous => 'up',
                    $latest < $previous => 'down',
                    default => 'flat',
                };
            }),

            /* with('recentPrices) */
            'recent_prices' => $this->whenLoaded('recentPrices', function () {
                return PriceHistoryResource::collection(
                    $this->recentPrices->sortBy('recorded_at')->values()
                );
            }),

            /* withCount([...]) */
            'supply_count' => $this->whenCounted('supply_count'),
            'demand_count' => $this->whenCounted('demand_count'),

            /* Admin paginated only */
            'supply_municipalities' => $this->when(
                isset($this->resource->supply_municipalities),
                fn () => $this->supply_municipalities
            ),
        ];
    }
}
