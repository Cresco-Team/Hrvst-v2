<?php

namespace App\Actions\Farmer;

use App\Models\Marketplace\FarmerSupply;

final class FulfillSupplyAction
{
    public function execute(FarmerSupply $supply): void
    {
        $supply->post->markAsFulfilled();
    }
}
