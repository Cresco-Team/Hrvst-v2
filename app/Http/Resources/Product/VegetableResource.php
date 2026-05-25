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
            'is_variety' => false,
            'image_url' => $this->getFirstMediaUrl('vegetable_image'),
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'varieties_count' => $this->whenCounted('varieties'),
            'varieties' => $this->whenLoaded(
                'varieties',
                fn () => VarietyResource::collection(
                    $this->varieties->each->setRelation('vegetable', $this->resource)
                )->resolve()
            ),
        ];
    }
}
