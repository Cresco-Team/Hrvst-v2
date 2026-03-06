<?php

namespace App\Actions\Supply;

use App\Models\Marketplace\FarmerSupply;

final class ArchiveSupplyAction
{
    public function __invoke(FarmerSupply $supply): void
    {
        $supply->post->markAsArchived();
    }
}
