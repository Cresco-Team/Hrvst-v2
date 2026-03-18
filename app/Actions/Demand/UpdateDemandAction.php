<?php

namespace App\Actions\Demand;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Product\Variety;

final class UpdateDemandAction
{
    public function handle(Post $post, array $validated): Post
    {
        if ($post->status !== PostStatus::Ongoing) {
            throw new \LogicException('Only ongoing demands can be updated.');
        }

        $fields = array_intersect_key($validated, array_flip([
            'variety_id', 'quantity_kg', 'offered_price', 'scheduled_date',
        ]));

        if (isset($fields['offered_price'])) {
            $variety = Variety::with('latestPrice')
                ->findOrFail($fields['variety_id'] ?? $post->variety_id);

            $fields['price_flag'] = PostPriceFlag::fromMarketPrice(
                (float) $fields['offered_price'],
                $variety->latestPrice
            );
        }

        $post->update($fields);

        return $post->fresh('variety');
    }
}
