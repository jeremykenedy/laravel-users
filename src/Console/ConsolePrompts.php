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
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\search')) {
            return (string) \Laravel\Prompts\search(
                $label,
                fn (string $query): array => array_values(array_filter(
                    $options,
                    fn (string $option): bool => str_contains(mb_strtolower($option), mb_strtolower($query))
                )),
                placeholder: 'Type to filter the supported options...'
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
        if (self::usesNativePrompts($command, $interactive) && function_exists('Laravel\\Prompts\\text')) {
            return \Laravel\Prompts\text($label, default: $default);
        }

        return (string) $command->ask($label, $default);
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
