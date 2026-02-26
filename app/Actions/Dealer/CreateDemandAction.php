<?php

namespace App\Actions\Dealer;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Variety;
use App\Models\Profiles\DealerProfile;

final class CreateDemandAction
{
    public function __invoke(DealerProfile $dealer, array $validated): DealerDemand
    {
        $variety = Variety::with('latestPrice')->findOrFail($validated['variety_id']);

        $demand = DealerDemand::create([
            'dealer_id'        => $dealer->id,
            'transaction_date' => $validated['transaction_date'],
        ]);

        $demand->post()->create([
            'user_id'       => $dealer->user_id,
            'variety_id'    => $validated['variety_id'],
            'title'         => $validated['title'] ?? null,
            'quantity_kg'   => $validated['quantity_kg'],
            'offered_price' => $validated['offered_price'] ?? null,
            'price_flag'    => PostPriceFlag::fromMarketPrice($validated['offered_price'], $variety->latestPrice),
            'status'        => PostStatus::Ongoing,
        ]);

        return $demand->load('post');
    }
}