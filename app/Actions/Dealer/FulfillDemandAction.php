<?php

namespace App\Actions\Dealer;

use App\Enums\PostStatus;
use App\Models\Marketplace\DealerDemand;

final class FulfillDemandAction
{
    public function __invoke(DealerDemand $demand): void
    {
        $demand->post->update(['status' => PostStatus::Fulfilled]);
    }
}
