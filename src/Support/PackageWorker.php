<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Support\Facades\Bus;
use jeremykenedy\laravelusers\Jobs\VerifyPackageWorker;
use Throwable;

class PackageWorker
{
    private const LEASE = 60;

    private const FAILURES = ['laravelusers::ui.package_composer_missing', 'laravelusers::ui.package_composer_manifest', 'laravelusers::ui.package_composer_vendor', 'laravelusers::ui.package_composer_application'];

    public static function probe(): void
    {
        $context = self::context();
        if ($context === null) {
            return;
        }
        $cache = ManagedPackages::cache();
        $key = self::key($context);
        $lock = $cache->lock($key.'.dispatch', 10);
        if (!$lock->get()) {
            return;
        }

        try {
            $pending = $cache->get($key);
            if (self::answer($context) !== null || (is_array($pending) && self::fresh($pending['issued_at'] ?? null))) {
                return;
            }
            $nonce = bin2hex(random_bytes(32));
            $cache->put($key, ['nonce' => $nonce, 'issued_at' => now()->timestamp], self::LEASE * 2);
        } finally {
            $lock->release();
        }

        try {
            Bus::dispatch((new VerifyPackageWorker($nonce, $context))
                ->onConnection(config('laravelusers.settings.packages.connection') ?? config('queue.default'))
                ->onQueue(config('laravelusers.settings.packages.queue', 'default')));
        } catch (Throwable $exception) {
            self::acknowledge($nonce, $context, 'laravelusers::ui.package_requirements_not_verified');

            throw $exception;
        }
    }

    public static function verified(): bool
    {
        $context = self::context();
        $answer = $context === null ? null : self::answer($context);

        return $answer !== null && $answer['failure'] === null;
    }

    public static function failure(): ?string
    {
        $context = self::context();

        return $context === null ? null : (self::answer($context)['failure'] ?? null);
    }

    public static function acknowledge(string $nonce, string $context, ?string $failure): void
    {
        if ($context !== self::context()) {
            return;
        }
        $cache = ManagedPackages::cache();
        $key = self::key($context);
        $pending = $cache->get($key);
        if (!is_array($pending) || ($pending['nonce'] ?? null) !== $nonce || !self::fresh($pending['issued_at'] ?? null)) {
            return;
        }
        if ($failure !== null && !in_array($failure, self::FAILURES, true)) {
            $failure = 'laravelusers::ui.package_requirements_not_verified';
        }
        $cache->add($key.'.'.$nonce, ['failure' => $failure, 'acknowledged_at' => now()->timestamp], self::LEASE);
    }

    private static function answer(string $context): ?array
    {
        $cache = ManagedPackages::cache();
        $key = self::key($context);
        $pending = $cache->get($key);
        if (!is_array($pending) || !is_string($pending['nonce'] ?? null)) {
            return null;
        }
        $answer = $cache->get($key.'.'.$pending['nonce']);

        return is_array($answer) && array_key_exists('failure', $answer) && self::fresh($answer['acknowledged_at'] ?? null) ? $answer : null;
    }

    private static function fresh(mixed $timestamp): bool
    {
        return is_int($timestamp) && $timestamp <= now()->timestamp && now()->timestamp - $timestamp < self::LEASE;
    }

    private static function key(string $context): string
    {
        return 'laravelusers.package.worker.'.$context;
    }

    private static function context(): ?string
    {
        $connection = config('laravelusers.settings.packages.connection') ?? config('queue.default');
        $queue = config('queue.connections.'.$connection, []);
        $store = config('laravelusers.settings.packages.cache') ?? config('cache.default');
        $cache = config('cache.stores.'.$store, []);
        if (!in_array($queue['driver'] ?? null, ['database', 'redis', 'sqs', 'beanstalkd'], true)
            || !isset($cache['driver']) || in_array($cache['driver'], ['array', 'null', 'octane'], true)) {
            return null;
        }

        return hash('sha256', serialize([$connection, $queue, config('laravelusers.settings.packages.queue', 'default'), $store, $cache, config('cache.prefix'), config('app.key')]));
    }
}
