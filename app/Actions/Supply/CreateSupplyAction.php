<?php

namespace App\Actions\Supply;

use App\Enums\PostStatus;
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
            'status' => PostStatus::Growing,
            'target_month' => $validated['target_month'],
            'estimated_total_weight' => $validated['estimated_total_weight'],
        ]);

        if ($image !== null) {
            $post->addMedia($image)->toMediaCollection('post_image');
        }

        return $post->load('vegetable');
    }
}
