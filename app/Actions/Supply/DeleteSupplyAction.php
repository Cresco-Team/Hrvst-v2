<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\Post;

final class DeleteSupplyAction
{
    public function handle(Post $post): void
    {
        $post->delete();
    }
}
