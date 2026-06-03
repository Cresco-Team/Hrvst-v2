<?php

namespace App\Http\Resources\Marketplace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                       => $this->id,
            'type'                     => $this->type->value,
            'status'                   => $this->status->value,
            'target_month'             => $this->target_month,
            'estimated_total_weight'   => $this->estimated_total_weight !== null
                ? (float) $this->estimated_total_weight
                : null,
            'scheduled_date'           => $this->scheduled_date?->format('M d, Y'),
            'time_slot'                => $this->time_slot?->value,
            'time_slot_label'          => $this->time_slot?->label(),
            'days_until_transaction'   => $this->scheduled_date
                ? (int) now()->diffInDays($this->scheduled_date, false)
                : null,
            'hearts_count'             => $this->hearts_count,
            'is_hearted'               => (bool) ($this->is_hearted ?? false),
            'created_at'               => $this->created_at->format('M d, Y'),
            'created_at_human'         => $this->created_at->diffForHumans(),

            'image_url' => $this->whenLoaded('media', fn () => $this->getFirstMediaUrl('post_image')),

            'vegetable' => $this->whenLoaded(
                'vegetable',
                fn () => new PostVegetableResource($this->vegetable)
            ),

            'items' => $this->whenLoaded(
                'postItems',
                fn () => PostItemInlineResource::collection($this->postItems)
            ),
        ];
    }
}
