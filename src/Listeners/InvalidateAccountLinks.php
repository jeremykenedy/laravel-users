<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Listeners;

use jeremykenedy\laravelusers\Support\AccountLinks;

class InvalidateAccountLinks
{
    public function __construct(private readonly AccountLinks $links)
    {
    }

    public function handle(string $event, array $payload): void
    {
        $this->links->invalidate($payload[0]);
    }
}
