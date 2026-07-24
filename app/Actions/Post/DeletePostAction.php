<?php

namespace App\Actions\Post;

use App\Models\Marketplace\Post;

final class DeletePostAction
{
    public function handle(Post $post): void
    {
        $post->delete();
    }
}
