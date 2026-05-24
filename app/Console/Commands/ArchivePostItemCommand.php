<?php

namespace App\Console\Commands;

use App\Actions\Post\ArchivePostItemAction;
use Illuminate\Console\Command;

class ArchivePostItemCommand extends Command
{
    protected $signature = 'post-items:archive';

    protected $description = 'Expire old dealer demands and farmer supplies';

    public function handle(ArchivePostItemAction $archiveOldPost): int
    {
        $postsArchived = $archiveOldPost();

        $this->info("Unsettled {$postsArchived}.");

        return self::SUCCESS;
    }
}
