<?php

namespace App\Http\Resources\Marketplace;

use App\Http\Resources\Product\VarietyResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $post = $this->post;
        $variety = $this->variety;
        $vegetable = $variety->vegetable;

        return [
            'id' => $this->id,
            'image_url' => $this->variety->vegetable->getFirstMediaUrl('vegetable_image'),
            'name' => $this->variety->vegetable->name.' '.$this->variety->name,
            'post_id' => $post->id,
            'status' => $this->status,

            // variety
            'variety' => $this->whenLoaded('variety', fn () => new VarietyResource($this->variety)),
            'variety_id' => $this->variety_id,
            'variety_name' => $variety->name,
            'vegetable_id' => $vegetable->id,
            'vegetable_name' => $vegetable->name,
            'category_name' => $vegetable->category->name,
            'quantity_kg' => (float) $this->quantity_kg,

            // schedule
            'scheduled_date' => $post->scheduled_date?->format('M d, Y'),
            'time_slot' => $post->time_slot?->value,
            'time_slot_label' => $post->time_slot?->label(),
            'days_until_transaction' => $post->scheduled_date
                ? (int) now()->diffInDays($post->scheduled_date, false)
                : null,

            'created_at' => $post->created_at->format('M d, Y'),
            'created_at_human' => $post->created_at->diffForHumans(),
        ];
    }
}
