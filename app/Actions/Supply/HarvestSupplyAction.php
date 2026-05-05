<?php

namespace App\Actions\Supply;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use Illuminate\Support\Facades\DB;

final class HarvestSupplyAction
{
    /**
     * @param  array{
     *     scheduled_date: string,
     *     time_slot: string,
     *     items: array<int, array{
     *         variety_id: int,
     *         quantity_kg: float,
     *         unit_price: float,
     *     }>
     * } $validated
     */
    public function handle(Post $post, array $validated): Post
    {
        if ($post->status !== PostStatus::Growing) {
            throw new \LogicException('Only growing supply posts can be harvested.');
        }

        DB::transaction(function () use ($post, $validated): void {
            $varietyIds = collect($validated['items'])->pluck('variety_id');
            $varieties = Variety::with('latestPrice')
                ->whereIn('id', $varietyIds)
                ->get()
                ->keyBy('id');

            foreach ($validated['items'] as $item) {
                $variety = $varieties->get($item['variety_id']);

                PostItem::create([
                    'post_id' => $post->id,
                    'variety_id' => $item['variety_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'unit_price' => $item['unit_price'],
                    'price_flag' => PostPriceFlag::fromMarketPrice(
                        (float) $item['unit_price'],
                        $variety?->latestPrice
                    ),
                ]);
            }

            // time_slot lives on post — set it alongside scheduled_date
            $post->time_slot = $validated['time_slot'];
            $post->markAsOngoing($validated['scheduled_date']);
        });

        return $post->fresh(['vegetable', 'postItems.variety']);
    }
}
