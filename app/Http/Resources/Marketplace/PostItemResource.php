<?php

namespace App\Http\Resources\Marketplace;

use App\Http\Resources\Product\VarietyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'variety_id' => $this->variety_id,
            'variety_name' => $this->whenLoaded('variety', fn () => $this->variety->name),
            'quantity_kg' => (float) $this->quantity_kg,
            'unit_price' => $this->unit_price !== null ? (float) $this->unit_price : null,
            'price_flag' => $this->price_flag?->value,
            'status' => $this->status,

            // Relationship
            'variety' => $this->whenLoaded('variety', fn () => new VarietyResource($this->variety)),
        ];
    }
}
