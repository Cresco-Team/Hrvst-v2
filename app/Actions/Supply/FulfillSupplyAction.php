<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\FarmerSupply;

final class FulfillSupplyAction
{
    public function __invoke(FarmerSupply $supply): void
    {
        $supply->post->markAsFulfilled();
    }
}
