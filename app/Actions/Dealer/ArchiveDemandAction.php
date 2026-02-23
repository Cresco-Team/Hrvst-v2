<?php

namespace App\Actions\Dealer;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;

final class ArchiveDealerDemandAction
{
    public function execute(DealerDemand $demand): void
    {
        $demand->post->update(['status' => PostStatus::Archived]);
    }
}
