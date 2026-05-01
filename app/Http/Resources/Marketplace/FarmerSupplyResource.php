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
            'status' => $this->status,
            'target_month' => $this->target_month,
            'scheduled_at' => $this->scheduled_at?->format('M d, Y'),
            'estimated_total_weight' => (float) $this->estimated_total_weight,
            'hearts_count' => $this->hearts_count ?? 0,
            'is_hearted' => (bool) ($this->is_hearted ?? false),
            'created_at' => $this->created_at->format('M d, Y'),
            'created_at_human' => $this->created_at->diffForHumans(),

            /* with('media') */
            'image_url' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('post_image')),

            /* with('vegetable.category') */
            'vegetable' => $this->whenLoaded('vegetable', fn () => [
                'id' => $this->vegetable->id,
                'name' => $this->vegetable->name,
                'category' => $this->vegetable->relationLoaded('category')
                    ? $this->vegetable->category->name
                    : null,
                'image_url' => $this->vegetable->getFirstMediaUrl('vegetable_image'),
            ]),

            /* with('postItems.variety') */
            'items' => $this->whenLoaded('postItems', fn () => $this->postItems->map(fn ($item) => [
                'id' => $item->id,
                'variety_id' => $item->variety_id,
                'variety_name' => $item->relationLoaded('variety') ? $item->variety->name : null,
                'quantity_kg' => (float) $item->quantity_kg,
                'unit_price' => $item->unit_price !== null ? (float) $item->unit_price : null,
                'price_flag' => $item->price_flag,
                'time_slot' => $item->time_slot?->value,
                'time_slot_label' => $item->time_slot?->label(),
            ])),
        ];
    }
}
