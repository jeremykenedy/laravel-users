<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

class Frontend
{
    public const FRAMEWORKS = ['bootstrap4', 'bootstrap5', 'tailwind'];

    public static function framework(): string
    {
        return config('laravelusers-ui.framework', config('laravelusers.frontend', 'bootstrap4'));
    }

    public static function theme(): string
    {
        return config('laravelusers-ui.theme', config('laravelusers.theme', 'light'));
    }

    public static function view(string $view): string
    {
        $defaults = [
            'show-users', 'create-user', 'show-user', 'edit-user',
        ];

        foreach ($defaults as $name) {
            if (self::framework() !== 'bootstrap4' && $view === 'laravelusers::usersmanagement.'.$name) {
                return 'laravelusers::modern.'.$name;
            }
        }

        return $view;
    }
}
