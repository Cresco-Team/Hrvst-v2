<?php

namespace App\Observers;

use App\Models\Marketplace\Post;

class PostObserver
{
    // Status tracking (archived/fulfilled KG stats) moved to PostItemObserver.
    // Post lifecycle is now two-stage: growing → harvested.
    // No stats columns are updated on Post status change.

    public function deleted(Post $post): void
    {
        // PostItems cascade-delete via FK. No manual stats adjustment needed
        // since PostItem::deleted fires on each item before cascade.
    }
}
