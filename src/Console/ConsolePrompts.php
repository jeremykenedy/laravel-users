<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;

class ConsolePrompts
{
    public static function select(Command $command, string $label, array $options, string $default, bool $interactive): string
    {
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\select')) {
            return (string) \Laravel\Prompts\select($label, $options, $default);
        }

        return (string) $command->choice($label, array_values($options), $default);
    }

    public static function search(Command $command, string $label, array $options, string $default, bool $interactive): string
    {
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\suggest')) {
            $choices = array_values(array_unique(array_merge(in_array($default, $options, true) ? [$default] : [], $options)));

            return (string) \Laravel\Prompts\suggest(
                $label,
                $choices,
                default: $default,
                placeholder: 'Type to filter the supported options...',
                validate: fn (string $value): ?string => in_array($value, $choices, true) ? null : 'Choose a supported CSS framework.'
            );
        }

        return (string) $command->choice($label, $options, $default);
    }

    public static function confirm(Command $command, string $label, bool $interactive, bool $default = false): bool
    {
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\confirm')) {
            return \Laravel\Prompts\confirm($label, $default);
        }

        return $command->confirm($label, $default);
    }

    public static function text(Command $command, string $label, string $default, bool $interactive): string
    {
        if (self::usesNativePrompts($command, $interactive) && $default !== '' && function_exists('Laravel\\Prompts\\suggest')) {
            return \Laravel\Prompts\suggest($label, [$default], default: $default);
        }
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\text')) {
            return \Laravel\Prompts\text($label, default: $default);
        }

        return (string) $command->ask($label, $default);
    }

    public static function note(Command $command, string $message, bool $interactive): void
    {
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\note')) {
            \Laravel\Prompts\note($message);

            return;
        }

        $command->line($message);
    }

    public static function intro(Command $command, string $title, string $description): void
    {
        if (function_exists('Laravel\\Prompts\\intro')) {
            \Laravel\Prompts\intro($title);
            \Laravel\Prompts\info($description);

            return;
        }

        $command->info($title);
        $command->line($description);
    }

    public static function outro(Command $command, string $message, bool $interactive): void
    {
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\outro')) {
            \Laravel\Prompts\outro($message);

            return;
        }

        $command->info($message);
    }

    public static function spin(Command $command, callable $callback, string $message, bool $interactive): mixed
    {
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\spin')) {
            return \Laravel\Prompts\spin($callback, $message);
        }

        return $callback();
    }

    public static function usesNativePrompts(Command $command, bool $interactive): bool
    {
        return $interactive && !$command->getLaravel()->runningUnitTests();
    }

    public static function table(Command $command, array $headers, array $rows, bool $interactive): void
    {
        if (!$interactive) {
            return;
        }

        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\table')) {
            \Laravel\Prompts\table($headers, $rows);

            return;
        }

        $command->table($headers, $rows);
    }
}
