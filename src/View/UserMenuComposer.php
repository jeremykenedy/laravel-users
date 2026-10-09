<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use jeremykenedy\laravelusers\Models\LoginActivity;
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
        if ($avatar) {
            $avatar['size'] = 28;
        }
        $view->with('navigationAvatar', $avatar);
        $view->with('navigationLastLogin', $lastLogin);
        $view->with('navigationLoginIcons', $this->activityIcons($lastLogin));
        $view->with('accountPageEnabled', $user instanceof Model && AccountPreferences::enabled($user));
        $view->with('canManageUsers', $user instanceof Model && UserAccess::canManageUsers($user));
    }

    private function activityIcons(?LoginActivity $login): array
    {
        return [
            'ip_address' => 'network',
            'device'     => 'device',
            'os'         => $this->platformIcon($login?->os, ['windows' => 'windows', 'mac' => 'apple', 'ios' => 'apple', 'android' => 'android', 'linux' => 'linux'], 'device'),
            'browser'    => $this->platformIcon($login?->browser, ['edge' => 'edge', 'firefox' => 'firefox', 'chrome' => 'chrome', 'safari' => 'safari'], 'browser'),
        ];
    }

    private function platformIcon(?string $name, array $icons, string $fallback): string
    {
        $name = mb_strtolower($name ?? '');
        foreach ($icons as $platform => $icon) {
            if (str_contains($name, $platform)) {
                return $icon;
            }
        }

        return $fallback;
    }
}
