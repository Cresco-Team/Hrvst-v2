<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VegetableSharedResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image_url' => $this->getFirstMediaUrl('vegetable_image'),
            'varieties_count' => $this->whenCounted('varieties'),

            // with category
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),

            // with varieties
            'varieties' => $this->whenLoaded('varieties', function () use ($request) {
                return $this->varieties->map(function ($variety) use ($request) {
                    return [
                        'id' => $variety->id,
                        'name' => $variety->name,

                        'supply_count' => $variety->supply_count,
                        'demand_count' => $variety->demand_count,

                        'latest_price' => $variety->when($variety->relationLoaded('latestPrice'),
                            fn () => $variety->latestPrice
                                ? (new PriceHistoryResource($variety->latestPrice))->toArray($request)
                                : null
                        ),
                        'price_trend' => $variety->when($variety->relationLoaded('lastTwoPrices'), function () use ($variety) {
                            $price = $variety->lastTwoPrices;

                            if ($price->count() < 2) {
                                return null;
                            }

                            $latest = (float) $price->first()->price_max;
                            $previous = (float) $price->last()->price_max;

                            return match (true) {
                                $latest > $previous => 'up',
                                $latest < $previous => 'down',
                                default => 'flat',
                            };
                        }),
                    ];
                });
            }),
        ];
    }
}
