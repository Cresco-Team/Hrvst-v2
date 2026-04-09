<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VarietyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->getFirstMediaUrl('variety_image'),
            'hearts_count' => $this->hearts_count,
            'is_hearted' => (bool) ($this->is_hearted ?? false),

            'vegetable' => [
                'id' => $this->vegetable->id,
                'name' => $this->vegetable->name,
                'category' => [
                    'id' => $this->vegetable->category->id,
                    'name' => $this->vegetable->category->name,
                ],
            ],

            'latest_price' => $this->whenLoaded('latestPrice', fn () => $this->latestPrice
                ? (new PriceHistoryResource($this->latestPrice))->toArray($request)
                : null
            ),
            'price_updated_human' => $this->whenLoaded('latestPrice', fn () => $this->latestPrice?->recorded_at->diffForHumans()),
            'price_updated_date' => $this->whenLoaded('latestPrice', fn () => $this->latestPrice?->recorded_at->format('M d, Y')),

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

            'supply_count' => $this->whenCounted('supply_count'),
            'demand_count' => $this->whenCounted('demand_count'),
        ];
    }
}
