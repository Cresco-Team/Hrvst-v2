<?php

namespace App\Actions\Supply;

use App\Enums\PostStatus;
use App\Models\Marketplace\Post;
use Illuminate\Http\UploadedFile;

final class UpdateSupplyAction
{
    public function handle(Post $post, array $validated, ?UploadedFile $image = null): Post
    {
        if ($post->status !== PostStatus::Growing) {
            throw new \LogicException('Only growing supplies can be updated via this action.');
        }

        $post->update(array_intersect_key($validated, array_flip([
            'vegetable_id', 'target_month', 'estimated_total_weight',
        ])));

        if ($image !== null) {
            $post->addMedia($image)->toMediaCollection('post_image');
        }

        return $post->fresh(['vegetable', 'media']);
    }
}
