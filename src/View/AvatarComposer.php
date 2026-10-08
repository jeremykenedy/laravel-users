<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\Avatar;

class AvatarComposer
{
    public function __construct(private Avatar $avatar)
    {
    }

    public function compose(View $view): void
    {
        $user = $view->getData()['user'] ?? null;
        if ($user && config('laravelusers.showProfileAvatar', true)) {
            $view->with('userAvatar', $this->avatar->forUser($user));
        }
        $view->with('userAvatars', $this->avatar->listing($view->getData()['users'] ?? []));
    }
}
