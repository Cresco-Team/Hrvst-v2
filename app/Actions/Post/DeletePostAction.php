<?php

namespace App\Actions\Post;

use App\Models\Schedule\Post;

final class DeletePostAction
{
    public function handle(Post $post): void
    {
        $post->delete();
    }
}
