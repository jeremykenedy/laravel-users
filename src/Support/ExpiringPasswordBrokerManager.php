<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Auth\Passwords\CacheTokenRepository;
use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Auth\Passwords\PasswordBrokerManager;
use ReflectionMethod;

class ExpiringPasswordBrokerManager extends PasswordBrokerManager
{
    protected function createTokenRepository(array $config)
    {
        return new ExpiringTokenRepository(
            $this->repository($config),
            fn (int $minutes) => $this->repository(array_replace($config, ['expire' => $minutes])),
            $this->app['encrypter'],
            (string) ($config['provider'] ?? ''),
            max((int) ($config['expire'] ?? 60), max(1, min(525600, (int) $this->app['config']->get('laravelusers.emails.reset_max_expire', 43200)))),
        );
    }

    private function repository(array $config)
    {
        $key = $this->app['config']->get('app.key');
        $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7)) : $key;
        $minutes = (int) ($config['expire'] ?? 60);
        if (($config['driver'] ?? '') === 'cache' && class_exists(CacheTokenRepository::class)) {
            return new ResetTokenCacheRepository($this->app['cache']->store($config['store'] ?? null), $this->app['hash'], $key, $minutes * 60, $config['throttle'] ?? 0);
        }
        $unit = (new ReflectionMethod(DatabaseTokenRepository::class, '__construct'))->getParameters()[4]->getDefaultValue() === 60 ? 1 : 60;

        return new ResetTokenDatabaseRepository($this->app['db']->connection($config['connection'] ?? null), $this->app['hash'], $config['table'], $key, $minutes * $unit, $config['throttle'] ?? 0);
    }
}
