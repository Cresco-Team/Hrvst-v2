<?php

namespace App\Actions\Demand;

use App\Models\Marketplace\DealerDemand;

final class ArchiveDemandAction
{
    public function __invoke(DealerDemand $demand): void
    {
        $demand->post->markAsArchived();
    }
}
