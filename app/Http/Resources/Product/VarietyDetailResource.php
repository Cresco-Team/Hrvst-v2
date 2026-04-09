<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VarietyDetailResource extends JsonResource
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

            'latest_price' => $this->latestPrice
                ? (new PriceHistoryResource($this->latestPrice))->toArray($request)
                : null,
            'price_updated_human' => $this->latestPrice?->recorded_at->diffForHumans(),
            'price_updated_date' => $this->latestPrice?->recorded_at->format('M d, Y'),

            'recent_prices' => PriceHistoryResource::collection(
                $this->recentPrices->sortBy('recorded_at')->values()
            )->toArray($request),

            'supply_count' => $this->supply_count,
            'demand_count' => $this->demand_count,
            'monthly_supply_kg' => (float) ($this->monthly_supply_kg ?? 0),
            'monthly_demand_kg' => (float) ($this->monthly_demand_kg ?? 0),

            'supply_municipalities' => $this->supply_municipalities,
            'monthly_activity' => $this->monthly_activity,
            'variety_calendar' => $this->variety_calendar,
        ];
    }
}
