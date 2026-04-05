<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\Marketplace\DealerDemandResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /* Always present */
            'id' => $this->id,
            'joined_at' => $this->created_at->format('M d, Y'),
            'joined_at_human' => $this->created_at->diffForHumans(),

            /* with('user') or with('user.media') */
            'user' => $this->whenLoaded('user', fn () => (new UserResource($this->user))->toArray($request)
            ),

            /* with('posts.*') */
            'demands' => $this->whenLoaded('posts', fn () => $this->posts
                ->map(fn ($demand) => (new DealerDemandResource($demand))->toArray($request))
                ->values()
                ->all()
            ),

            /* withCount(['demands as ongoing_demands_count' => ...]) */
            'ongoing_demands_count' => $this->whenCounted('ongoing_demands_count'),
        ];
    }
}
