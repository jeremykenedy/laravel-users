<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\Contracts\Session\Session;
use Illuminate\View\View;
use Jeremykenedy\LaravelToast\Facades\Toast;
use jeremykenedy\laravelusers\Support\UserNotifications;

class NotificationsComposer
{
    public function __construct(private readonly Session $session)
    {
    }

    public function compose(View $view): void
    {
        if (UserNotifications::useToast() && config('laravelusers.enablePackageBootstapAlerts', true) && $this->session->has('message') && !request()->attributes->get('laravelusers.message_toast')) {
            Toast::info((string) $this->session->get('message'));
            request()->attributes->set('laravelusers.message_toast', true);
        }
    }
}
