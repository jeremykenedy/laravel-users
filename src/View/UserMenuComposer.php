<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\UserAccess;
use jeremykenedy\laravelusers\Support\UserActivity;
use jeremykenedy\laravelusers\Support\UserSettings;

class UserMenuComposer
{
    public function __construct(private readonly Avatar $avatars, private readonly UserSettings $settings, private readonly UserActivity $activity)
    {
    }

    public function compose(View $view): void
    {
        $this->settings->load();
        $user = Auth::user();
        $avatar = $user instanceof Model ? $this->avatars->forUser($user) : null;
        $lastLogin = $user instanceof Model ? $this->activity->lastLogin($user) : null;
        $icons = ['ip_address' => 'network', 'device' => 'device', 'os' => 'device', 'browser' => 'browser'];
        if ($lastLogin) {
            $os = mb_strtolower((string) $lastLogin->os);
            $browser = mb_strtolower((string) $lastLogin->browser);
            $icons['os'] = str_contains($os, 'windows') ? 'windows' : (str_contains($os, 'mac') || str_contains($os, 'ios') ? 'apple' : (str_contains($os, 'android') ? 'android' : (str_contains($os, 'linux') ? 'linux' : 'device')));
            $icons['browser'] = str_contains($browser, 'edge') ? 'edge' : (str_contains($browser, 'firefox') ? 'firefox' : (str_contains($browser, 'chrome') ? 'chrome' : (str_contains($browser, 'safari') ? 'safari' : 'browser')));
        }
        if ($avatar) {
            $avatar['size'] = 28;
        }
        $view->with('navigationAvatar', $avatar);
        $view->with('navigationLastLogin', $lastLogin);
        $view->with('navigationLoginIcons', $icons);
        $view->with('accountPageEnabled', $user instanceof Model && AccountPreferences::enabled($user));
        $view->with('canManageUsers', $user instanceof Model && UserAccess::canManageUsers($user));
    }
}
