<?php

namespace App\Console\Commands;

use App\Actions\Post\ExpirePostItemsAction;
use Illuminate\Console\Command;

class ExpirePostItemsCommand extends Command
{
    protected $signature = 'post-items:expire';

    protected $description = 'Expire ongoing post items past their scheduled date';

    public function handle(ExpirePostItemsAction $expirePostItems): int
    {
        $count = $expirePostItems();

        $this->info("Expired {$count} post item(s).");

        return self::SUCCESS;
    }
}
