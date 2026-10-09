<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;
use jeremykenedy\laravelusers\Support\AccountLinks;

class PruneAccountLinksCommand extends Command
{
    protected $signature = 'laravelusers:prune-account-links';

    protected $description = 'Remove expired deleted-account email links';

    public function handle(AccountLinks $links): int
    {
        $this->info($links->prune().' expired account links removed.');

        return self::SUCCESS;
    }
}
