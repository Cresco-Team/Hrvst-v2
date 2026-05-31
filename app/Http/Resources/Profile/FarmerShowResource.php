<?php

namespace App\Http\Resources\Profile;

use App\Http\Resources\Marketplace\PostItemResource;
use App\Http\Resources\Marketplace\PostResource;
use Illuminate\Http\Request;

class FarmerShowResource extends FarmerIndexResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'supplies' => $this->whenLoaded('posts', fn () => PostResource::collection($this->posts)->resolve($request)),

            'supply_items' => $this->whenLoaded(
                'supplyItems',
                fn () => PostItemResource::collection($this->supplyItems)->resolve($request)
            ),
        ]);
    }
}
