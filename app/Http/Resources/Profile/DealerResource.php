<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\Marketplace\PostItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'joined_at' => $this->created_at->toDateString(),
            'joined_at_human' => $this->created_at->diffForHumans(),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'phone_number' => $this->user->phone_number,
                'avatar_url' => $this->user->getFirstMediaUrl('avatar'),
            ]),

            // List view — populated by withCount in paginated()
            'ongoing_demands_count' => $this->whenCounted('ongoing_demands_count'),

            // Sidebar detail view — posts loaded with ongoing postItems
            'demands' => $this->whenLoaded('posts', fn () => $this->posts
                ->flatMap(fn ($post) => $post->relationLoaded('postItems') ? $post->postItems : collect())
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'variety_name' => $item->relationLoaded('variety') ? $item->variety->name : null,
                    'quantity_kg' => (float) $item->quantity_kg,
                    'status' => $item->status,
                ])
                ->values()
                ->toArray()
            ),

            // Full show view — populated by show(); demandItems = HasManyThrough PostItems
            'demand_items' => $this->whenLoaded(
                'demandItems',
                fn () => PostItemResource::collection($this->demandItems)->resolve($request)
            ),
        ];
    }
}
