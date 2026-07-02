<?php

namespace App\Actions\Demand;

use App\Enums\PostItemStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use Illuminate\Support\Facades\DB;

final class UpdateDemandAction
{
    public function handle(Post $post, array $validated): Post
    {
        DB::transaction(function () use ($post, $validated): void {
            $post->update(array_intersect_key($validated, array_flip([
                'scheduled_date', 'time_slot',
            ])));

            if (! empty($validated['items'])) {
                $post->postItems()
                    ->where('status', PostItemStatus::Ongoing)
                    ->delete();

                foreach ($validated['items'] as $item) {
                    PostItem::create([
                        'post_id' => $post->id,
                        'vegetable_id' => $item['vegetable_id'],
                        'quantity_kg' => $item['quantity_kg'],
                        'status' => PostItemStatus::Ongoing,
                    ]);
                }
            }
        });

        return $post->fresh(['postItems.vegetable']);
    }
}
