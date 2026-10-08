<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\Avatar;

class AvatarComposer
{
    public function __construct(private readonly Avatar $avatar)
    {
    }

    public function compose(View $view): void
    {
        $view->with('userAvatars', $this->avatar->listing($view->getData()['users'] ?? []));
    }
}
