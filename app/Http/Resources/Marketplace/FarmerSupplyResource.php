<?php

namespace App\Http\Resources\Marketplace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerSupplyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'scheduled_date' => $this->scheduled_date?->format('M d, Y'),
            'time_slot' => $this->time_slot?->value,
            'time_slot_label' => $this->time_slot?->label(),
            'created_at' => $this->created_at->format('M d, Y'),
            'created_at_human' => $this->created_at->diffForHumans(),

            'image_url' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('post_image')),

            'items' => $this->whenLoaded('postItems', fn () => $this->postItems->map(fn ($item) => [
                'id' => $item->id,
                'variety_id' => $item->variety_id,
                'variety_name' => $item->relationLoaded('variety') ? $item->variety->name : null,
                'vegetable_name' => $item->relationLoaded('variety') && $item->variety->relationLoaded('vegetable')
                    ? $item->variety->vegetable->name
                    : null,
                'quantity_kg' => (float) $item->quantity_kg,
                'status' => $item->status,
            ])),
        ];
    }
}
