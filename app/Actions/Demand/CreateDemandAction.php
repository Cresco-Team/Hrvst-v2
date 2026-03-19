<?php

namespace App\Actions\Demand;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Product\Variety;
use App\Models\Profiles\DealerProfile;

final class CreateDemandAction
{
    public function handle(DealerProfile $dealer, array $validated): Post
    {
        $variety = Variety::with('latestPrice')->findOrFail($validated['variety_id']);

        return Post::create([
            'user_id' => $dealer->user_id,
            'variety_id' => $validated['variety_id'],
            'type' => PostType::Demand,
            'quantity_kg' => $validated['quantity_kg'],
            'offered_price' => $validated['offered_price'] ?? null,
            'price_flag' => PostPriceFlag::fromMarketPrice($validated['offered_price'] ?? 0, $variety->latestPrice),
            'status' => PostStatus::Ongoing,
            'scheduled_date' => $validated['scheduled_date'],
            'time_slot' => $validated['time_slot'],
        ]);
    }
}
