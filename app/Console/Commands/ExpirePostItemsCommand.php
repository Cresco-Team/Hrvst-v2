<?php

namespace App\Console\Commands;

use App\Actions\Post\ExpirePostItemsAction;
use Illuminate\Console\Command;

class ExpirePostItemsCommand extends Command
{
    protected $signature = 'post-items:expire';

    protected $description = 'Backstop sweep: force-expire ongoing post items whose action window lapsed but were never displayed';

    public function handle(ExpirePostItemsAction $expirePostItems): int
    {
        $count = $expirePostItems();

        $this->info("Expired {$count} post item(s).");

        return self::SUCCESS;
    }
}
