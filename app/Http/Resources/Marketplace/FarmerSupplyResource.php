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
            'quantity_kg' => (float) $this->quantity_kg,
            'offered_price' => (float) $this->offered_price,
            'price_flag' => $this->price_flag,
            'status' => $this->status,
            'scheduled_date' => $this->scheduled_date?->format('M d, Y'),
            'time_slot' => $this->time_slot?->value,
            'time_slot_label' => $this->time_slot?->label(),
            'days_until_expiration' => $this->scheduled_date
                ? (int) now()->diffInDays($this->scheduled_date, false)
                : null,
            'hearts_count' => $this->hearts_count ?? 0,
            'is_hearted' => (bool) ($this->is_hearted ?? false),
            'created_at' => $this->created_at->format('M d, Y'),
            'created_at_human' => $this->created_at->diffForHumans(),

            /* with('media') */
            'image_url' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('post_image')
            ),

            /* with('variety.vegetable.category', 'variety.media') */
            'variety' => $this->whenLoaded('variety', function () {
                $variety = $this->variety;

                return [
                    'id' => $variety->id,
                    'name' => $variety->name,
                    'vegetable' => $variety->relationLoaded('vegetable')
                        ? $variety->vegetable->name
                        : null,
                    'category' => $variety->relationLoaded('vegetable') && $variety->vegetable->relationLoaded('category')
                        ? $variety->vegetable->category->name
                        : null,
                    'image_url' => $variety->getFirstMediaUrl('variety_image'),
                ];
            }),
        ];
    }
}
