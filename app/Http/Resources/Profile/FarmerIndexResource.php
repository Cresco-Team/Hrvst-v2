<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FarmerIndexResource extends JsonResource
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
                    $this->relationLoaded('barangay') ? $this->barangay?->name : null,
                    $this->relationLoaded('municipality') ? $this->municipality?->name : null,
                    $this->relationLoaded('province') ? $this->province?->name : null,
                ])),
                'barangay' => $this->whenLoaded('barangay', fn () => $this->barangay?->name),
                'municipality' => $this->whenLoaded('municipality', fn () => $this->municipality?->name),
                'province' => $this->whenLoaded('province', fn () => $this->province?->name),
            ],
        ];
    }
}
