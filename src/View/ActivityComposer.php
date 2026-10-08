<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\DeletedUsers;
use jeremykenedy\laravelusers\Support\UserActivity;

class ActivityComposer
{
    public function __construct(private UserActivity $activity, private DeletedUsers $deleted)
    {
    }

    public function compose(View $view): void
    {
        $data = $view->getData();
        $activity = $this->activity->listing($data['users'] ?? [], (bool) config('laravelusers.showLastLoginDetailsColumn', false));
        $view->with('userActivity', $activity);
        $view->with('onlineUsers', array_map(fn ($record) => $record['online'], $activity));
        if (isset($data['users'])) {
            $view->with('hasDeletedUsers', $this->deleted->exists());
        }
        if (isset($data['user'])) {
            $view->with('userOnline', $this->activity->isOnline($data['user']));
            $view->with('lastLogin', $this->activity->lastLogin($data['user']));
        }
    }
}
