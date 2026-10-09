<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\View;
use Jeremykenedy\LaravelToast\Facades\Toast;
use Jeremykenedy\LaravelToast\Providers\ToastServiceProvider;

class UserNotifications
{
    public static function toastInstalled(): bool
    {
        return class_exists(ToastServiceProvider::class) && View::exists('toast::toasts');
    }

    public static function useToast(): bool
    {
        return in_array(config('laravelusers.notifications.driver', 'alert'), ['toast', 'both'], true) && self::toastInstalled();
    }

    public static function useAlerts(): bool
    {
        return !self::useToast() || config('laravelusers.notifications.driver') === 'both';
    }

    public static function toasts(Session $session): array
    {
        if (!self::useToast() || !config('laravelusers.enablePackageBootstapAlerts', true)) {
            return [];
        }
        $toasts = Toast::get();
        if (config('toast.convert_flash', true)) {
            foreach (['message' => 'info', 'status' => 'info', 'info' => 'info', 'success' => 'success', 'error' => 'error', 'warning' => 'warning'] as $key => $type) {
                $message = $session->get($key);
                if (is_string($message) && $message !== '') {
                    $toasts = Toast::append($toasts, Toast::build($type, $message));
                }
            }
        }
        $fields = array_fill_keys(['id', 'type', 'message', 'title', 'duration', 'position', 'auto_dismiss', 'pause_on_hover', 'show_icon', 'show_progress', 'progress_direction', 'progress_position', 'opacity', 'show_border', 'show_close', 'dir', 'enter_animation', 'enter_duration', 'exit_animation', 'exit_duration'], true);

        return array_map(fn ($toast) => array_intersect_key($toast, $fields), $toasts);
    }
}
