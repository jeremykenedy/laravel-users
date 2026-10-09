<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Listeners;

use jeremykenedy\laravelusers\Support\AccountLinks;

class InvalidateAccountLinks
{
    /**
     * Laravel wildcard event listeners receive the event name before the payload.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function __construct(private readonly AccountLinks $links)
    {
    }

    /**
     * Laravel wildcard event listeners receive the event name before the payload.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function handle(string $event, array $payload): void
    {
        $this->links->invalidate($payload[0]);
    }
}
