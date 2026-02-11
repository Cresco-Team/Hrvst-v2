<?php

namespace App\Console\Commands;

use App\Models\Product\Planting;
use App\PlantingStatus;
use Illuminate\Console\Command;

class ArchiveExpiredPlantings extends Command
{
    protected $signature = 'app:archive-expired-plantings';

    protected $description = 'Archives plantings that have expired by updating their status to "archived".';

    public function handle()
    {
        $count = Planting::where('status', PlantingStatus::Available)
            ->where('expiration_date', '<', now()->dateToString())
            ->update(['status' => PlantingStatus::Archived]);

        $this->info("Successfully archived {$count} expired plantings.");
    }
}
