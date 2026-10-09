<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Validation\Rule;
use Jeremykenedy\LaravelToast\Support\ToastAnimations;

class ToastSettings
{
    public static function fields(): array
    {
        return [
            'position'           => ['type' => 'select', 'default' => 'top-right', 'options' => ['top-right', 'top-left', 'top-center', 'bottom-right', 'bottom-left', 'bottom-center']],
            'dir'                => ['type' => 'select', 'default' => 'ltr', 'options' => ['ltr', 'rtl']],
            'duration'           => ['type' => 'number', 'default' => 5000, 'min' => 0, 'max' => 3600000, 'step' => 100],
            'max_visible'        => ['type' => 'number', 'default' => 5, 'min' => 0, 'max' => 100, 'step' => 1],
            'opacity'            => ['type' => 'number', 'default' => 1, 'min' => 0, 'max' => 1, 'step' => 0.05],
            'enter_animation'    => ['type' => 'select', 'default' => 'none', 'options' => self::animations()],
            'enter_duration'     => ['type' => 'number', 'default' => 0.5, 'min' => 0, 'max' => 5, 'step' => 0.1],
            'exit_animation'     => ['type' => 'select', 'default' => 'none', 'options' => self::animations()],
            'exit_duration'      => ['type' => 'number', 'default' => 0.5, 'min' => 0, 'max' => 5, 'step' => 0.1],
            'progress_direction' => ['type' => 'select', 'default' => 'rtl', 'options' => ['rtl', 'ltr']],
            'progress_position'  => ['type' => 'select', 'default' => 'top', 'options' => ['top', 'bottom']],
            'auto_dismiss'       => ['type' => 'checkbox', 'default' => true],
            'pause_on_hover'     => ['type' => 'checkbox', 'default' => true],
            'stack'              => ['type' => 'checkbox', 'default' => true],
            'show_icons'         => ['type' => 'checkbox', 'default' => true],
            'show_border'        => ['type' => 'checkbox', 'default' => true],
            'show_close'         => ['type' => 'checkbox', 'default' => true],
            'show_progress'      => ['type' => 'checkbox', 'default' => true],
            'convert_flash'      => ['type' => 'checkbox', 'default' => true],
        ];
    }

    private static function animations(): array
    {
        return class_exists(ToastAnimations::class) && method_exists(ToastAnimations::class, 'names') ? ToastAnimations::names() : ['none', 'fade', 'slide-left', 'slide-right', 'slide-top', 'slide-bottom'];
    }

    public static function values(): array
    {
        $values = [];
        foreach (self::fields() as $key => $field) {
            $values[$key] = config('toast.'.$key, $field['default']);
        }

        return $values;
    }

    public static function rules(): array
    {
        $allowed = UserNotifications::toastInstalled() && UserAccess::allows('edit_notifications');
        $rules = ['toast' => [$allowed ? 'sometimes' : 'prohibited', 'array:'.implode(',', array_keys(self::fields()))]];
        foreach (self::fields() as $key => $field) {
            $rules['toast.'.$key] = match ($field['type']) {
                'select'   => ['sometimes', Rule::in($field['options'])],
                'checkbox' => ['sometimes', 'boolean'],
                default    => ['sometimes', $field['step'] === 1 || $key === 'duration' ? 'integer' : 'numeric', 'min:'.$field['min'], 'max:'.$field['max']],
            };
        }

        return $rules;
    }

    public static function saveOptions(array $data): array
    {
        $values = self::values();
        foreach ($data['toast'] ?? [] as $key => $value) {
            $field = self::fields()[$key] ?? null;
            if ($field) {
                $values[$key] = match ($field['type']) {
                    'checkbox' => (bool) $value,
                    'number'   => $field['step'] === 1 || $key === 'duration' ? (int) $value : (float) $value,
                    default    => $value,
                };
            }
        }

        return $values;
    }

    public static function apply(array $values): void
    {
        if (!UserNotifications::toastInstalled()) {
            return;
        }
        foreach (array_intersect_key($values, self::fields()) as $key => $value) {
            config(['toast.'.$key => $value]);
        }
    }
}
