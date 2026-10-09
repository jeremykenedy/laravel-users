<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\Contracts\Session\Session;
use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\UserNotifications;

class NotificationsComposer
{
    public function __construct(private readonly Session $session)
    {
    }

    public function compose(View $view): void
    {
        $view->with('userToasts', UserNotifications::toasts($this->session));
    }
}
