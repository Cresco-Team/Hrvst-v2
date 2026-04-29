<?php

namespace App\Actions\Demand;

use App\Enums\PostStatus;
use App\Models\Marketplace\Post;

final class UpdateDemandAction
{
    public function handle(Post $post, array $validated): Post
    {
        if ($post->status !== PostStatus::Ongoing) {
            throw new \LogicException('Only ongoing demands can be updated.');
        }

        $fields = array_intersect_key($validated, array_flip([
            'vegetable_id', 'quantity_kg', 'scheduled_date', 'time_slot',
        ]));

        $post->update($fields);

        return $post->fresh('vegetable');
    }
}
