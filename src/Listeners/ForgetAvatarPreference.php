<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Listeners;

use Illuminate\Database\Eloquent\SoftDeletes;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\AvatarPreferences;

class ForgetAvatarPreference
{
    /**
     * Laravel wildcard event listeners receive the event name before the payload.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function handle(string $event, array $data): void
    {
        $model = config('laravelusers.defaultUserModel');
        $user = $data[0] ?? null;
        if (!$user instanceof $model || (in_array(SoftDeletes::class, class_uses_recursive($user), true) && !$user->isForceDeleting())) {
            return;
        }
        AvatarPreferences::forget($user);
        AppearancePreferences::forget($user);
        AccountPreferences::forget($user);
    }
}
