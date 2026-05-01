<?php

namespace App\Actions\Demand;

use App\Enums\PostPriceFlag;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use App\Models\Profiles\DealerProfile;
use Illuminate\Support\Facades\DB;

final class CreateDemandAction
{
    /**
     * @param  array{
     *     vegetable_id: int,
     *     scheduled_at: string,
     *     items: array<int, array{
     *         variety_id: int,
     *         quantity_kg: float,
     *         unit_price: float|null,
     *         time_slot: string,
     *     }>
     * } $validated
     */
    public function handle(DealerProfile $dealer, array $validated): Post
    {
        return DB::transaction(function () use ($dealer, $validated): Post {
            /** @var Post $post */
            $post = Post::create([
                'user_id' => $dealer->user_id,
                'vegetable_id' => $validated['vegetable_id'],
                'type' => PostType::Demand,
                'status' => PostStatus::Ongoing,
                'scheduled_at' => $validated['scheduled_at'],
            ]);

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
                    'time_slot' => $item['time_slot'],
                ]);
            }

            return $post->load(['vegetable', 'postItems.variety']);
        });
    }
}
