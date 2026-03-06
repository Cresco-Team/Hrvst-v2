<?php

namespace App\Console\Commands;

use App\Actions\Post\ArchiveOldPostsAction;
use Illuminate\Console\Command;

class ArchiveOldPostsCommand extends Command
{
    protected $signature = 'posts:archive';
    protected $description = 'Expire old dealer demands and farmer supplies';

    public function handle(ArchiveOldPostsAction $archiveOldPost): int
    {
        $postsExpired = $archiveOldPost();

        $this->info("Expired {$postsExpired}.");

        return self::SUCCESS;
    }
}
