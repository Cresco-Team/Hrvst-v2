<?php

namespace App\Actions\Supply;

use App\Enums\PostItemStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Support\Facades\DB;

final class CreateSupplyAction
{
    public function handle(FarmerProfile $farmer, array $validated): Post
    {
        return DB::transaction(function () use ($farmer, $validated): Post {
            $post = Post::create([
                'user_id' => $farmer->user_id,
                'type' => PostType::Supply,
                'scheduled_date' => $validated['scheduled_date'],
                'time_slot' => $validated['time_slot'],
            ]);

            foreach ($validated['items'] as $item) {
                $post->postItems()->create([
                    'variety_id' => $item['variety_id'],
                    'quantity_kg' => $item['quantity_kg'],
                    'status' => PostItemStatus::Ongoing,
                ]);
            }

            return $post->load('postItems.variety.vegetable');
        });
    }
}
