<?php

namespace App\Actions\Demand;

use App\Enums\PostItemStatus;
use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use Illuminate\Support\Facades\DB;

final class UpdateDemandAction
{
    public function handle(Post $post, array $validated): Post
    {
        if ($post->status !== PostStatus::Harvested) {
            throw new \LogicException('Only active demand posts can be updated.');
        }

        DB::transaction(function () use ($post, $validated): void {
            $post->update(array_intersect_key($validated, array_flip([
                'vegetable_id', 'scheduled_date', 'time_slot',
            ])));

            if (! empty($validated['items'])) {
                $post->postItems()->delete();

                $varietyIds = collect($validated['items'])->pluck('variety_id');
                $varieties = Variety::with('latestPrice')
                    ->whereIn('id', $varietyIds)
                    ->get()
                    ->keyBy('id');

                foreach ($validated['items'] as $item) {
                    $variety = $varieties->get($item['variety_id']);
                    $unitPrice = $item['unit_price'] ?? null;

                    PostItem::create([
                        'post_id' => $post->id,
                        'variety_id' => $item['variety_id'],
                        'quantity_kg' => $item['quantity_kg'],
                        'unit_price' => $unitPrice,
                        'price_flag' => $unitPrice !== null
                            ? PostPriceFlag::fromMarketPrice((float) $unitPrice, $variety?->latestPrice)
                            : PostPriceFlag::Fair,
                        'status' => PostItemStatus::Ongoing,
                    ]);
                }
            }
        });

        return $post->fresh(['vegetable', 'postItems.variety']);
    }
}
