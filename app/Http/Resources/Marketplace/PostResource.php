<?php

namespace App\Http\Resources\Marketplace;

use App\Enums\PostType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'hearts_count' => $this->hearts_count ?? 0,
            'is_hearted' => (bool) ($this->is_hearted ?? false),
            'created_at' => $this->created_at->format('M d, Y'),
            'created_at_human' => $this->created_at->diffForHumans(),

            // Supply
            'target_month' => $this->when(
                $this->type === PostType::Supply,
                $this->target_month
            ),
            'estimated_total_weight' => $this->when(
                $this->type === PostType::Supply,
                (float) $this->estimated_total_weight
            ),

            // Demand
            'scheduled_date' => $this->when(
                $this->type === PostType::Demand,
                $this->scheduled_date?->format('M d, Y')
            ),
            'time_slot' => $this->when(
                $this->type === PostType::Demand,
                $this->time_slot?->value
            ),
            'time_slot_label' => $this->when(
                $this->type === PostType::Demand,
                $this->time_slot?->label()
            ),

            // Relationships
            'vegetable' => $this->whenLoaded('vegetable', fn () => [
                'id' => $this->vegetable->id,
                'name' => $this->vegetable->name,
                'category' => $this->vegetable->relationLoaded('category')
                                   ? $this->vegetable->category->name
                                   : null,
                'image_url' => $this->vegetable->getFirstMediaUrl('vegetable_image'),
            ]),
            'items' => $this->whenLoaded('postItems', fn () => PostItemResource::collection($this->postItems)->resolve($request)
            ),
        ];
    }
}
