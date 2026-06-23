<?php

namespace App\Http\Resources\Product;

use App\DTOs\Product\VarietyAnalyticsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VarietyDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            'vegetable' => [
                'id' => $this->vegetable->id,
                'name' => $this->vegetable->name,
                'category' => [
                    'id' => $this->vegetable->category->id,
                    'name' => $this->vegetable->category->name,
                    'image_url' => $this->vegetable->getFirstMediaUrl('vegetable_image'),
                ],
            ],

            'supply_count' => $this->supply_count,
            'demand_count' => $this->demand_count,
            'monthly_supply_kg' => (float) ($this->monthly_supply_kg ?? 0),
            'monthly_demand_kg' => (float) ($this->monthly_demand_kg ?? 0),

            'supply_municipalities' => $this->supply_municipalities,
            'monthly_activity' => $this->monthly_activity,
            'variety_calendar' => $this->variety_calendar,

            'analytics' => $this->analytics instanceof VarietyAnalyticsDTO
                ? $this->analytics->toArray()
                : null,
        ];
    }
}
