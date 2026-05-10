<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\Marketplace\PostItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerResource extends JsonResource
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

            'location' => [
                'full_address' => implode(', ', array_filter([
                    $this->whenLoaded('barangay', fn () => $this->barangay?->name),
                    $this->whenLoaded('municipality', fn () => $this->municipality?->name),
                    $this->whenLoaded('province', fn () => $this->province?->name),
                ])),
                'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay?->name),
                'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality?->name),
                'province' => $this->whenLoaded('province', fn () => $this->province?->name),
                'coordinates' => [
                    'lat' => (float) $this->latitude,
                    'lng' => (float) $this->longitude,
                ],
            ],

            // List view — populated by withCount in paginated()
            'ongoing_supplies_count' => $this->whenCounted('ongoing_supplies_count'),

            // Sidebar detail view — populated by details(); posts loaded with ongoing postItems
            // Flatten postItems across all loaded posts into a simple summary array
            'supplies' => $this->whenLoaded('posts', fn () => $this->posts
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

            // Full show view — populated by show(); supplyItems = HasManyThrough PostItems
            'supply_items' => $this->whenLoaded(
                'supplyItems',
                fn () => PostItemResource::collection($this->supplyItems)->resolve($request)
            ),

            // Count of growing posts loaded for the show view
            'growing_posts_count' => $this->whenLoaded(
                'growing_posts_count',
                fn () => $this->posts->count()
            ),
        ];
    }
}
