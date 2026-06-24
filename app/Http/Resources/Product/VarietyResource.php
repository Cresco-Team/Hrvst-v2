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

            'vegetable' => $this->whenLoaded('vegetable',
                fn () => new VegetableResource(($this->vegetable))
            ),

            'supply_count' => $this->whenCounted('supply_count'),
            'demand_count' => $this->whenCounted('demand_count'),
        ];
    }
}
