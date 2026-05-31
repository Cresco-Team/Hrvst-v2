<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;

class FarmerDetailResource extends FarmerIndexResource
{
    public function toArray(Request $request): array
    {
        return array_merge(parent::toArray($request), [
            'coordinates' => [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude,
            ],

            'ongoing_supply_items_count' => $this->whenCounted('ongoing_supply_items_count'),
        ]);
    }
}
