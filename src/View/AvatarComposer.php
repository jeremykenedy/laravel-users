<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\View;

use Illuminate\View\View;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\Avatar;
use jeremykenedy\laravelusers\Support\Frontend;

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
        $appearance = AppearancePreferences::colors($view->getData()['users'] ?? ($user ? [$user] : []));
        $view->with('userAppearance', $appearance);
        if ($user) {
            $view->with('profileAppearance', $appearance[$user->getKey()] ?? Frontend::profileColors() + ['gradient' => config('laravelusers.profileCardGradient', true)]);
        }
    }
}
