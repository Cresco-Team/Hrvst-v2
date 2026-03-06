<?php

namespace App\Actions\Demand;

use App\Models\Marketplace\DealerDemand;

final class FulfillDemandAction
{
    public function __invoke(DealerDemand $demand): void
    {
        $demand->post->markAsFulfilled();
    }
}
