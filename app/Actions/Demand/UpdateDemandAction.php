<?php

namespace App\Actions\Demand;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use Illuminate\Support\Facades\DB;

final class UpdateDemandAction
{
    public function handle(Post $post, array $validated): Post
    {
        DB::transaction(function () use ($post, $validated): void {
            $post->update(array_intersect_key($validated, array_flip([
                'vegetable_id', 'scheduled_date', 'time_slot',
            ])));

            if (! empty($validated['items'])) {
                $post->postItems()
                    ->where('status', PostItemStatus::Ongoing)
                    ->delete();

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
                        'status' => PostItemStatus::Ongoing,
                    ]);
                }
            }
        });

        return $post->fresh(['vegetable', 'postItems.variety']);
    }
}
