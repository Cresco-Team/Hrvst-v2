<?php

namespace App\Actions\Supply;

use App\Enums\PostItemStatus;
use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use Illuminate\Support\Facades\DB;

final class HarvestSupplyAction
{
    public function handle(Post $post, array $validated): Post
    {
        if ($post->status !== PostStatus::Growing) {
            throw new \LogicException('Only growing supply posts can be harvested.');
        }

        DB::transaction(function () use ($post, $validated): void {
            foreach ($validated['items'] as $item) {
                PostItem::create([
                    'post_id' => $post->id,
                    'variety_id' => $item['variety_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'status' => PostItemStatus::Ongoing,
                ]);
            }

            $post->time_slot = $validated['time_slot'];
            $post->markAsHarvested($validated['scheduled_date']);
        });

        return $post->fresh(['vegetable', 'postItems.variety']);
    }
}
