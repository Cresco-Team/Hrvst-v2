<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VegetableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_variety' => false, // Tanstack child table
            'image_url' => $this->getFirstMediaUrl('vegetable_image'),
            'varieties_count' => $this->whenCounted('varieties'),

            // with category
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),

            // with varieties
            'varieties' => $this->whenLoaded('varieties',
                fn () => VarietyResource::collection($this->varieties),
            ),
        ];
    }
}
