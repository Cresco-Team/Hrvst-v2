<?php

namespace App\Console\Commands;

use App\Services\Dealer\RequestService;
use App\Services\Farmer\OfferingService;
use Illuminate\Console\Command;

class ExpireAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:expire';
    protected $description = 'Expire old dealer requests and farmer offerings';

    public function handle(): int
    {
        $requestsExpired = RequestService::expireOldRequests();
        $offeringsExpired = OfferingService::expireOldOfferings();

        $this->info("Expired {$requestsExpired} dealer requests and {$offeringsExpired} farmer offerings.");

        return self::SUCCESS;
    }
}
