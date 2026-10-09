<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

class PasswordRules
{
    /**
     * The existing creation mode selects the documented create and update password limits.
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public static function settings(bool $creating = false): array
    {
        $min = max(1, (int) config('laravelusers.password.min', 6));

        return [
            'min'        => $min,
            'max'        => $creating && config('laravelusers.password.create_max') === null ? null : max($min, (int) config('laravelusers.password.'.($creating ? 'create_max' : 'max'), 20)),
            'mixed_case' => (bool) config('laravelusers.password.mixed_case', false),
            'numbers'    => (bool) config('laravelusers.password.numbers', false),
            'symbols'    => (bool) config('laravelusers.password.symbols', false),
        ];
    }

    /**
     * The existing boolean options preserve the public validation helper contract.
     *
     * @SuppressWarnings("PHPMD.BooleanArgumentFlag")
     */
    public static function validation(bool $creating = false, bool $required = true): array
    {
        $settings = self::settings($creating);
        $rules = [$required ? 'required' : 'nullable', 'string', 'confirmed', 'min:'.$settings['min']];
        if ($settings['max'] !== null) {
            $rules[] = 'max:'.$settings['max'];
        }
        foreach (['mixed_case' => 'regex:/^(?=.*\\p{Ll})(?=.*\\p{Lu}).*$/us', 'numbers' => 'regex:/\\p{N}/u', 'symbols' => 'regex:/[^\\p{L}\\p{N}\\s]/u'] as $setting => $rule) {
            if ($settings[$setting]) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }
}
