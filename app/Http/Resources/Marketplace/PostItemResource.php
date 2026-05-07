<?php

namespace App\Http\Resources\Marketplace;

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
            'post_id' => $post->id,
            'status' => $this->status,

            // variety
            'variety_id' => $this->variety_id,
            'variety_name' => $variety->name,
            'variety_image_url' => $variety->getFirstMediaUrl('variety_image'),
            'vegetable_name' => $vegetable->name,
            'category_name' => $vegetable->category->name,

            // pricing
            'quantity_kg' => (float) $this->quantity_kg,
            'unit_price' => $this->unit_price !== null ? (float) $this->unit_price : null,
            'price_flag' => $this->price_flag?->value,

            // schedule
            'scheduled_date' => $post->scheduled_date?->format('M d, Y'),
            'time_slot' => $post->time_slot?->value,
            'time_slot_label' => $post->time_slot?->label(),
            'days_until_transaction' => $post->scheduled_date
                ? (int) now()->diffInDays($post->scheduled_date, false)
                : null,

            // farmer location (null for demand posts)
            'municipality' => $post->farmerProfile?->municipality?->name,

            // interaction
            'hearts_count' => $post->hearts_count,
            'is_hearted' => (bool) ($post->is_hearted ?? false),
            'created_at' => $post->created_at->format('M d, Y'),
            'created_at_human' => $post->created_at->diffForHumans(),
        ];
    }
}
