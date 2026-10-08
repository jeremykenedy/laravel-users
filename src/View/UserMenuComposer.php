<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserSettings;

class UserMenuComposer
{
    public function __construct(private readonly Avatar $avatars, private readonly UserSettings $settings)
    {
    }

    public function compose(View $view): void
    {
        $this->settings->load();
        $user = Auth::user();
        $avatar = $user instanceof Model ? $this->avatars->forUser($user) : null;
        if ($avatar) {
            $avatar['size'] = 28;
        }
        $view->with('navigationAvatar', $avatar);
        $view->with('accountPageEnabled', $user instanceof Model && AccountPreferences::enabled($user));
        $view->with('canManageUsers', $user instanceof Model && UserAccess::canManageUsers($user));
    }
}
