<?php

namespace App\Http\Resources\Marketplace;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerDemandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /* Always present */
            'id'                      => $this->id,
            'transaction_date'        => $this->transaction_date->format('M d, Y'),
            'days_until_transaction'  => (int) now()->diffInDays($this->transaction_date, false),
            'created_at'              => $this->created_at->format('M d, Y'),
            'created_at_human'        => $this->created_at->diffForHumans(),

            /* with('post') */
            'title'         => $this->whenLoaded('post', fn () => $this->post->title),
            'quantity_kg'   => $this->whenLoaded('post', fn () => (float) $this->post->quantity_kg),
            'offered_price' => $this->whenLoaded('post', fn () => (float) $this->post->offered_price),
            'price_flag'    => $this->whenLoaded('post', fn () => $this->post->price_flag),
            'status'        => $this->whenLoaded('post', fn () => $this->post->status),

            /* with('post.variety.vegetable.category', 'post.variety.media') */
            'variety' => $this->whenLoaded('post', function () {
                if (! $this->post->relationLoaded('variety')) {
                    return null;
                }

                $variety = $this->post->variety;

                return [
                    'id'        => $variety->id,
                    'name'      => $variety->name,
                    'vegetable' => $variety->relationLoaded('vegetable') ? $variety->vegetable->name : null,
                    'category'  => $variety->relationLoaded('vegetable') && $variety->vegetable->relationLoaded('category')
                        ? $variety->vegetable->category->name
                        : null,
                    'image_url' => $variety->getFirstMediaUrl('variety_image'),
                ];
            }),
        ];
    }
}
