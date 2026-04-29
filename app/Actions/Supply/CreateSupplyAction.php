<?php

namespace App\Actions\Supply;

use App\Enums\PostType;
use App\Models\Marketplace\Post;
use App\Models\Profiles\FarmerProfile;
use Illuminate\Http\UploadedFile;

final class CreateSupplyAction
{
    public function handle(FarmerProfile $farmer, array $validated, ?UploadedFile $image = null): Post
    {
        /** @var Post $post */
        $post = Post::create([
            'user_id' => $farmer->user_id,
            'vegetable_id' => $validated['vegetable_id'],
            'type' => PostType::Supply,
            'quantity_kg' => $validated['quantity_kg'],
            'scheduled_date' => $validated['scheduled_date'],
            'time_slot' => $validated['time_slot'],
        ]);

        if ($image !== null) {
            $post->addMedia($image)->toMediaCollection('post_image');
        }

        return $post->load('vegetable');
    }
}
