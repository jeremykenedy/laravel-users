<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Support\Facades\View;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;

class UserNotifications
{
    public static function toastInstalled(): bool
    {
        return class_exists(ToastServiceProvider::class) && View::exists('toast::toasts');
    }

    public static function useToast(): bool
    {
        return config('laravelusers.notifications.driver', 'alert') === 'toast' && self::toastInstalled();
    }
}
