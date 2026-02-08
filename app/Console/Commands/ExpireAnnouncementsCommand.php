<?php

namespace App\Console\Commands;

use App\Services\Dealer\DealerRequestService;
use App\Services\Farmer\FarmerOfferingService;
use Illuminate\Console\Command;

class ExpireAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:expire';
    protected $description = 'Expire old dealer requests and farmer offerings';

    public function handle(): int
    {
        $requestsExpired = DealerRequestService::expireOldRequests();
        $offeringsExpired = FarmerOfferingService::expireOldOfferings();

        $this->info("Expired {$requestsExpired} dealer requests and {$offeringsExpired} farmer offerings.");

        return self::SUCCESS;
    }
}
