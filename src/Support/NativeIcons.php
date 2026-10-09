<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

class NativeIcons
{
    public static function name(string $action): ?string
    {
        static $actions;
        $actions ??= json_decode(file_get_contents(__DIR__.'/../resources/js/runtime/icon-actions.json'), true, 512, JSON_THROW_ON_ERROR);

        return $actions[$action] ?? null;
    }

    public static function submitAction(array $form): string
    {
        if ($form['danger'] ?? false) {
            return 'delete';
        }
        if (($form['async'] ?? false) && isset($form['values']['operation'])) {
            return $form['values']['operation'];
        }

        return self::name($form['id']) ? $form['id'] : 'save';
    }
}
