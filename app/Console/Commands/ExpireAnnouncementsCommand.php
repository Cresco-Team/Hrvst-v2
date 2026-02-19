<?php

namespace App\Console\Commands;

use App\Services\Dealer\DemandService;
use App\Services\Farmer\OfferingService;
use Illuminate\Console\Command;

class ExpireAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:expire';
    protected $description = 'Expire old dealer demands and farmer offerings';

    public function handle(): int
    {
        $demandsExpired = DemandService::expireOldDemands();
        $offeringsExpired = OfferingService::expireOldOfferings();

        $this->info("Expired {$demandsExpired} dealer demands and {$offeringsExpired} farmer offerings.");

        return self::SUCCESS;
    }
}
