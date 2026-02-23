<?php

namespace App\Actions\Farmer;

use App\Enums\PostStatus;
use App\Models\Marketplace\FarmerSupply;

final class ArchiveSupplyAction
{
    public function execute(FarmerSupply $supply): void
    {
        $supply->post->update(['status' => PostStatus::Archived]);
    }
}
