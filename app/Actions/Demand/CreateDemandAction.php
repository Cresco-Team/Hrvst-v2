<?php

namespace App\Actions\Demand;

use App\Enums\PostStatus;
use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Profiles\DealerProfile;

final class CreateDemandAction
{
    public function handle(DealerProfile $dealer, array $validated): Post
    {
        return Post::create([
            'user_id' => $dealer->user_id,
            'vegetable_id' => $validated['vegetable_id'],
            'type' => PostType::Demand,
            'quantity_kg' => $validated['quantity_kg'],
            'status' => PostStatus::Ongoing,
            'scheduled_date' => $validated['scheduled_date'],
            'time_slot' => $validated['time_slot'],
        ]);
    }
}
