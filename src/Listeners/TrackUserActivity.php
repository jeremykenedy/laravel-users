<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
        if ($event->guard !== config('laravelusers.activity.guard', 'web')
            || !$event->user instanceof Model || !$this->matchesUserModel($event->user)) {
            return;
        }

        if ($event instanceof Login) {
            $this->activity->recordLogin($event->user, $this->request);
        }
        $this->activity->touch($event->user, $this->request, $event instanceof Logout);
    }

    private function matchesUserModel(Model $user): bool
    {
        $model = config('laravelusers.defaultUserModel');
        if (!$user instanceof $model) {
            return false;
        }
        $expected = new $model();

        return $user->getTable() === $expected->getTable()
            && ($user->getConnectionName() ?? config('database.default')) === ($expected->getConnectionName() ?? config('database.default'));
    }

    public function deleted(string $event, array $data): void
    {
        $model = config('laravelusers.defaultUserModel');
        if (($data[0] ?? null) instanceof $model) {
            $user = $data[0];
            $softDelete = in_array(SoftDeletes::class, class_uses_recursive($user), true) && !$user->isForceDeleting();
            $this->activity->forget($user, !$softDelete);
        }
    }
}
