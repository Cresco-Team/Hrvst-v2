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

            /* Automatically available via $with in Model */
            'vegetable' => [
                'id' => $this->vegetable->id,
                'name' => $this->vegetable->name,
                'category' => [
                    'id' => $this->vegetable->category->id,
                    'name' => $this->vegetable->category->name,
                ],
            ],

            /* with('latestPrice) */
            'latest_price' => $this->whenLoaded('latestPrice', function () use ($request) {
                return $this->latestPrice
                    ? (new PriceHistoryResource($this->latestPrice))->toArray($request)
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
            'recent_prices' => $this->whenLoaded('recentPrices', function () use ($request) {
                return $this->recentPrices
                    ->sortBy('recorded_at')
                    ->values()
                    ->map(fn ($price) => (new PriceHistoryResource($price))->toArray($request))
                    ->all();
            }),

            /* withCount([...]) */
            'supply_count' => $this->whenCounted('supply_count'),
            'demand_count' => $this->whenCounted('demand_count'),

            'monthly_supply_kg' => $this->when(
                isset($this->resource->monthly_supply_kg),
                fn () => (float) ($this->monthly_supply_kg ?? 0)
            ),

            'monthly_demand_kg' => $this->when(
                isset($this->resource->monthly_demand_kg),
                fn () => (float) ($this->monthly_demand_kg ?? 0)
            ),

            /* Admin paginated only */
            'supply_municipalities' => $this->when(
                isset($this->resource->supply_municipalities),
                fn () => $this->supply_municipalities
            ),

            'monthly_activity' => $this->when(
                isset($this->resource->monthly_activity),
                fn () => $this->monthly_activity
            ),

            'quantity_stats' => $this->when(
                isset($this->resource->quantity_stats),
                fn () => $this->quantity_stats
            ),

            'variety_calendar' => $this->when(
                isset($this->resource->variety_calendar),
                fn () => $this->variety_calendar
            ),

            'calendar_summary' => $this->when(
                isset($this->resource->calendar_summary),
                fn () => $this->calendar_summary
            ),
        ];
    }
}
