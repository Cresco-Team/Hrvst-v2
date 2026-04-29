<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\Post;
use Illuminate\Http\UploadedFile;

final class UpdateSupplyAction
{
    public function handle(Post $post, array $validated, ?UploadedFile $image = null): Post
    {
        $fields = array_intersect_key($validated, array_flip([
            'vegetable_id', 'quantity_kg', 'scheduled_date', 'time_slot',
        ]));

        $post->update($fields);

        if ($image !== null) {
            $post->addMedia($image)->toMediaCollection('post_image');
        }

        return $post->fresh(['vegetable', 'media']);
    }
}
