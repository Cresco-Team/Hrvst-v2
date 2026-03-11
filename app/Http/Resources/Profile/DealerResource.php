<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\Marketplace\DealerDemandResource;
use App\Http\Resources\Profile\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /* Always present */
            'id'              => $this->id,
            'is_approved'     => $this->is_approved,
            'joined_at'       => $this->created_at->format('M d, Y'),
            'joined_at_human' => $this->created_at->diffForHumans(),

            /* with('user') or with('user.media') */
            'user' => $this->whenLoaded('user', fn () =>
                new UserResource($this->user)
            ),

            /* with('demands.*') */
            'demands' => $this->whenLoaded('demands', fn () =>
                DealerDemandResource::collection($this->demands)
            ),

            /* withCount(['demands as ongoing_demands_count' => ...]) */
            'ongoing_demands_count' => $this->whenCounted('ongoing_demands_count'),

            /* Admin-only: set $dealer->document_url = route('admin.dealers.document', $dealer->id) in service */
            'document_url' => $this->when(
                isset($this->resource->document_url),
                fn () => $this->document_url
            ),
        ];
    }
}
