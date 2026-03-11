<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\Marketplace\FarmerSupplyResource;
use App\Http\Resources\Profile\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            /* Always present */
            'id'             => $this->id,
            'is_approved'    => $this->is_approved,
            'joined_at'      => $this->created_at->format('M d, Y'),
            'joined_at_human'=> $this->created_at->diffForHumans(),

            /* with('user') or with('user.media') */
            'user' => $this->whenLoaded('user', fn () =>
                new UserResource($this->user)
            ),

            /* with('province', 'municipality', 'barangay') — all three must be loaded */
            'location' => $this->when(
                $this->relationLoaded('province')
                && $this->relationLoaded('municipality')
                && $this->relationLoaded('barangay'),
                fn () => [
                    'province'     => $this->province->name,
                    'municipality' => $this->municipality->name,
                    'barangay'     => $this->barangay->name,
                    'full_address' => "{$this->barangay->name}, {$this->municipality->name}, {$this->province->name}",
                    'coordinates'  => [
                        'lat' => $this->latitude,
                        'lng' => $this->longitude,
                    ],
                ]
            ),

            /* with('media') */
            'farm_url' => $this->whenLoaded('media', fn () =>
                $this->getFirstMediaUrl('farm_photo')
            ),

            /* with('supplies.*') */
            'supplies' => $this->whenLoaded('supplies', fn () =>
                FarmerSupplyResource::collection($this->supplies)
            ),

            /* withCount(['supplies as ongoing_supplies_count' => ...]) */
            'ongoing_supplies_count' => $this->whenCounted('ongoing_supplies_count'),

            /* Admin-only: set $farmer->document_url = route(...) in service before wrapping */
            'document_url' => $this->when(
                isset($this->resource->document_url),
                fn () => $this->document_url
            ),
        ];
    }
}
