<?php

namespace App\Console\Commands;

use App\Enums\Billing\SubscriptionStatus;
use App\Models\Billing\Subscription;
use Illuminate\Console\Command;

class ExpireSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:expire';

    protected $description = 'Flip subscriptions past their end date to expired.';

    public function handle(): int
    {
        $count = Subscription::query()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Cancelled])
            ->where('ends_at', '<=', now())
            ->update(['status' => SubscriptionStatus::Expired]);

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
