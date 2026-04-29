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

            'image_url' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('post_image')),

            'vegetable' => $this->whenLoaded('vegetable', function () {
                $vegetable = $this->vegetable;

                return [
                    'id' => $vegetable->id,
                    'name' => $vegetable->name,
                    'category' => $vegetable->relationLoaded('category')
                        ? $vegetable->category->name
                        : null,
                    'image_url' => $vegetable->getFirstMediaUrl('vegetable_image'),
                ];
            }),
        ];
    }
}
