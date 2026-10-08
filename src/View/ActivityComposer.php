<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\UserActivity;

class ActivityComposer
{
    public function __construct(private UserActivity $activity) {}

    public function compose(View $view): void
    {
        $data = $view->getData();
        $online = [];
        foreach ($data['users'] ?? [] as $user) {
            $online[$user->getKey()] = $this->activity->isOnline($user);
        }
        $view->with('onlineUsers', $online);
        if (isset($data['user'])) {
            $view->with('userOnline', $this->activity->isOnline($data['user']));
            $view->with('lastLogin', $this->activity->lastLogin($data['user']));
        }
    }
}
