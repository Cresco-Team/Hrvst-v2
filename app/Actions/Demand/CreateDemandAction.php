<?php

namespace App\Actions\Demand;

use App\Enums\PostItemStatus;
use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Marketplace\PostItem;
use App\Models\Product\Variety;
use App\Models\Profiles\DealerProfile;
use Illuminate\Support\Facades\DB;

final class CreateDemandAction
{
    public function handle(DealerProfile $dealer, array $validated): Post
    {
        return DB::transaction(function () use ($dealer, $validated): Post {
            /** @var Post $post */
            $post = Post::create([
                'user_id' => $dealer->user_id,
                'vegetable_id' => $validated['vegetable_id'],
                'type' => PostType::Demand,
                'status' => PostStatus::Harvested,
                'scheduled_date' => $validated['scheduled_date'],
                'time_slot' => $validated['time_slot'],
            ]);

            $varietyIds = collect($validated['items'])->pluck('variety_id');
            $varieties = Variety::with('latestPrice')
                ->whereIn('id', $varietyIds)
                ->get()
                ->keyBy('id');

            foreach ($validated['items'] as $item) {
                PostItem::create([
                    'post_id' => $post->id,
                    'variety_id' => $item['variety_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'status' => PostItemStatus::Ongoing,
                ]);
            }

            return $post->load(['vegetable', 'postItems.variety']);
        });
    }
}
