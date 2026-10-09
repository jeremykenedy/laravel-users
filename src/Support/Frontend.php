<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Support\Facades\Route;

class Frontend
{
    public const FRAMEWORKS = ['bootstrap4', 'bootstrap5', 'tailwind', 'materialize', 'material3', 'bulma', 'foundation'];

    public const RELEASE_FRAMEWORKS = ['bootstrap4', 'bootstrap5'];

    private const CLASSES = [
        'tailwind' => [
            'shell'  => 'lu:mx-auto lu:max-w-6xl lu:px-6 lu:py-8',
            'panel'  => 'lu:rounded-xl lu:border lu:shadow-sm',
            'scroll' => 'lu:overflow-x-auto',
            'table'  => 'lu:w-full lu:text-left',
            'input'  => 'lu:block lu:w-full lu:rounded-lg',
            'select' => 'lu:w-full',
        ],
        'materialize' => [
            'shell'         => 'container',
            'panel'         => 'card',
            'table'         => 'striped highlight',
            'field'         => 'row',
            'field-label'   => 'col s12 m3',
            'field-control' => 'col s12 m9',
            'select'        => 'browser-default',
        ],
        'material3' => [
            'shell' => 'md-typescale-body-large',
            'panel' => 'lu-material-surface',
        ],
        'bulma' => [
            'shell'         => 'container',
            'panel'         => 'card',
            'scroll'        => 'table-container',
            'table'         => 'table is-fullwidth is-striped',
            'field'         => 'field is-horizontal',
            'field-label'   => 'field-label is-normal',
            'field-control' => 'field-body',
            'control'       => 'control',
            'input'         => 'input',
        ],
        'foundation' => [
            'shell'         => 'grid-container',
            'panel'         => 'card',
            'table'         => 'hover',
            'field'         => 'grid-x grid-padding-x',
            'field-label'   => 'cell small-12 medium-3',
            'field-control' => 'cell small-12 medium-9',
        ],
    ];

    public static function stylesheet(): ?string
    {
        return match (self::framework()) {
            'materialize', 'material3', 'bulma', 'foundation' => self::framework().'.css',
            default                                           => null,
        };
    }

    public static function classes(string $element): string
    {
        $framework = self::framework();
        $classes = self::CLASSES[$framework] ?? [
            'shell'  => 'container py-4',
            'panel'  => 'card',
            'scroll' => 'table-responsive',
            'table'  => 'table',
            'input'  => 'form-control',
            'select' => $framework === 'bootstrap4' ? 'form-control' : 'form-select',
        ];

        return $classes[$element] ?? '';
    }

    public static function framework(): string
    {
        return config('laravelusers-ui.framework', config('laravelusers.frontend', 'bootstrap4'));
    }

    public static function theme(): string
    {
        return config('laravelusers-ui.theme', config('laravelusers.theme', 'light'));
    }

    public static function homeUrl(): string
    {
        foreach (Route::getRoutes() as $route) {
            if ($route->uri() === '/' && in_array('GET', $route->methods(), true)) {
                return url('/');
            }
        }

        foreach (['home', 'index'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            if ($route && in_array('GET', $route->methods(), true) && !$route->parameterNames()) {
                return route($name);
            }
        }

        return route('users');
    }

    /**
     * The existing theme option preserves the public color helper contract.
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public static function profileColors(string $setting = 'profileCardColor', string $default = '#2458b7', bool $dark = false): array
    {
        $prefix = $setting === 'editCardColor' ? 'editCard' : 'profileCard';
        $color = ($dark ? config('laravelusers.'.$prefix.'DarkColor') : null) ?? config('laravelusers.'.$setting, $default);
        $strength = ($dark ? config('laravelusers.'.$prefix.'DarkGradientStrength') : null) ?? config('laravelusers.'.$prefix.'GradientStrength', 50);
        $highlight = ($dark ? config('laravelusers.'.$prefix.'DarkGradientHighlightColor') : null) ?? config('laravelusers.'.$prefix.'GradientHighlightColor', '#ffffff');

        return self::gradientColors(self::colors($color, $default), $strength, $highlight);
    }

    public static function gradientColors(array $colors, mixed $strength, mixed $highlight = '#ffffff'): array
    {
        $strength = max(0, min(100, (int) $strength));
        $opacity = $strength / 50;
        $shade = substr($colors['shade'], 0, 7).sprintf('%02x', min(255, (int) round(hexdec(substr($colors['shade'], 7)) * $opacity)));

        return array_replace($colors, ['shade' => $shade, 'highlight' => self::colors($highlight, '#ffffff')['base'].sprintf('%02x', (int) round(72 * $opacity)), 'strength' => $strength]);
    }

    public static function colors(mixed $color, string $default = '#2458b7'): array
    {
        if (!is_string($color) || !preg_match('/^#(?:[a-f0-9]{3}|[a-f0-9]{6})$/iD', $color)) {
            $color = $default;
        }
        $hex = substr($color, 1);
        if (strlen($hex) === 3) {
            $hex = implode('', array_map(static fn ($digit) => $digit.$digit, str_split($hex)));
        }
        $channels = array_map(static function ($channel) {
            $value = hexdec($channel) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));
        $light = (0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2]) > 0.179;

        return ['base' => '#'.$hex, 'text' => $light ? '#000' : '#fff', 'shade' => $light ? '#ffffff48' : '#00000050'];
    }

    public static function view(string $view): string
    {
        $defaults = [
            'show-users', 'create-user', 'show-user', 'edit-user', 'deleted-users',
        ];

        foreach ($defaults as $name) {
            if (self::framework() !== 'bootstrap4' && $view === 'laravelusers::usersmanagement.'.$name) {
                return 'laravelusers::modern.'.$name;
            }
        }

        return $view;
    }
}
