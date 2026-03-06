<?php

namespace App\Actions\Demand;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;
use App\Models\Product\Variety;

final class UpdateDemandAction
{
    public function __invoke(DealerDemand $demand, array $validated): DealerDemand
    {
        $post = $demand->post;

        if ($post->status !== PostStatus::Ongoing) {
            throw new \LogicException('Only ongoing demands can be updated.');
        }

        $demandFields = array_intersect_key($validated, array_flip(['transaction_date']));

        $postFields = array_intersect_key($validated, array_flip([
            'title', 'variety_id', 'quantity_kg', 'offered_price',
        ]));

        if (isset($postFields['offered_price'])) {
            $variety = Variety::with('latestPrice')->findOrFail($postFields['variety_id'] ?? $post->variety_id);
            $postFields['price_flag'] = PostPriceFlag::fromMarketPrice($postFields['offered_price'], $variety->latestPrice);
        }

        if (!empty($demandFields)) {
            $demand->update($demandFields);
        }

        if (!empty($postFields)) {
            $post->update($postFields);
        }

        return $demand->fresh('post');
    }
}
