<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use jeremykenedy\laravelusers\Support\UserActivity;

class TrackUserActivity
{
    public function __construct(private UserActivity $activity, private Request $request)
    {
    }

    public function handle(Authenticated|Login|Logout $event): void
    {
        if (!config('laravelusers.activity.login', false) && !config('laravelusers.activity.online', false)) {
            return;
        }
        $model = config('laravelusers.defaultUserModel');
        if ($event->guard !== config('laravelusers.activity.guard', 'web')
            || !$event->user instanceof Model || !$event->user instanceof $model
            || $event->user->getTable() !== (new $model())->getTable()
            || ($event->user->getConnectionName() ?? config('database.default')) !== ((new $model())->getConnectionName() ?? config('database.default'))) {
            return;
        }

        if ($event instanceof Login) {
            $this->activity->recordLogin($event->user, $this->request);
        }
        $this->activity->touch($event->user, $this->request, $event instanceof Logout);
    }

    public function deleted(string $event, array $data): void
    {
        $model = config('laravelusers.defaultUserModel');
        if (($data[0] ?? null) instanceof $model) {
            $this->activity->forget($data[0]);
        }
    }
}
