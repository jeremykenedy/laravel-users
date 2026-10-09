<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;
use jeremykenedy\laravelusers\Actions\CleanupDeletedUsers;

class CleanupDeletedUsersCommand extends Command
{
    protected $signature = 'laravelusers:prune-deleted';

    protected $description = 'Permanently delete soft-deleted users past the configured retention period';

    public function handle(CleanupDeletedUsers $cleanup): int
    {
        $count = $cleanup->handle();
        $this->info('Permanently deleted '.$count.' users.');

        return self::SUCCESS;
    }
}
