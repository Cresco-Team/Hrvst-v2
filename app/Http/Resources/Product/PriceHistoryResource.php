<?php

namespace App\Http\Resources\Product;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PriceHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'price_min'   => (float) $this->price_min,
            'price_max'   => (float) $this->price_max,
            'recorded_at' => $this->recorded_at->format('M d, Y'),
            'freshness'   => $this->resolveFreshness(),
        ];
    }

    private function resolveFreshness(): string
    {
        $daysOld = $this->recorded_at->diffInDays(now());

        return match (true) {
            $daysOld <= 7  => 'recent',
            $daysOld <= 30 => 'stable',
            $daysOld <= 90 => 'very stable',
            default        => 'stale',
        };
    }
}
