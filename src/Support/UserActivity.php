<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Cache\CacheManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use jeremykenedy\laravelusers\Models\LoginActivity;
use Throwable;
use UAParser\Parser;

class UserActivity
{
    public function __construct(private CacheManager $cache)
    {
    }

    public function recordLogin(Model $user, Request $request): void
    {
        if (!config('laravelusers.activity.login', false)) {
            return;
        }

        try {
            $agent = Parser::create()->parse(mb_substr($request->userAgent() ?? '', 0, 2048));
            $device = $agent->device->family;
            if ($device === 'Other' && in_array($agent->os->family, ['Windows', 'Mac OS X', 'Linux', 'Ubuntu', 'Chrome OS'], true)) {
                $device = 'Desktop';
            }

            $record = (new LoginActivity())->fill([
                'user_key'      => $this->key($user),
                'last_login_at' => Carbon::now(),
                'ip_address'    => $request->ip(),
                'device'        => mb_substr($device, 0, 255),
                'os'            => mb_substr($agent->os->toString(), 0, 255),
                'browser'       => mb_substr($agent->ua->toString(), 0, 255),
            ])->getAttributes();
            $this->logins()->upsert([$record], ['user_key'], ['last_login_at', 'ip_address', 'device', 'os', 'browser']);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function lastLogin(Model $user): ?object
    {
        if (!config('laravelusers.activity.login', false)) {
            return null;
        }

        try {
            return $this->logins()->where('user_key', $this->key($user))
                ->first(['last_login_at', 'ip_address', 'device', 'os', 'browser']);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    public function touch(Model $user, Request $request, bool $logout = false): void
    {
        if (!config('laravelusers.activity.online', false) || !$request->hasSession()) {
            return;
        }

        try {
            $store = $this->cache->store(config('laravelusers.activity.cache_store'));
            $key = 'laravelusers:online:'.$this->key($user);
            $session = $request->session()->get('laravelusers.presence_token');
            if (!$session) {
                if ($logout) {
                    return;
                }
                $session = bin2hex(random_bytes(32));
                $request->session()->put('laravelusers.presence_token', $session);
            }
            $ttl = max(1, (int) config('laravelusers.activity.online_seconds', 300));
            $store->lock($key.':lock', 5)->block(1, function () use ($store, $key, $session, $ttl, $logout) {
                $sessions = array_filter($store->get($key, []), fn ($seen) => $seen > Carbon::now()->timestamp - $ttl);
                if ($logout) {
                    unset($sessions[$session]);
                } else {
                    $sessions[$session] = Carbon::now()->timestamp;
                }
                if ($sessions) {
                    $store->put($key, $sessions, $ttl);
                } else {
                    $store->forget($key);
                }
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    public function isOnline(Model $user): ?bool
    {
        if (!config('laravelusers.activity.online', false)) {
            return null;
        }

        try {
            $sessions = $this->cache->store(config('laravelusers.activity.cache_store'))
                ->get('laravelusers:online:'.$this->key($user), []);
            $ttl = max(1, (int) config('laravelusers.activity.online_seconds', 300));

            return (bool) array_filter($sessions, fn ($seen) => $seen > Carbon::now()->timestamp - $ttl);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function key(Model $user): string
    {
        return hash('sha256', implode('|', [get_class($user), $user->getConnectionName() ?? config('database.default'), $user->getTable(), $user->getKey()]));
    }

    public function forget(Model $user): void
    {
        if (config('laravelusers.activity.login', false)) {
            try {
                $this->logins()->where('user_key', $this->key($user))->delete();
            } catch (Throwable $exception) {
                report($exception);
            }
        }
        if (config('laravelusers.activity.online', false)) {
            try {
                $this->cache->store(config('laravelusers.activity.cache_store'))->forget('laravelusers:online:'.$this->key($user));
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    private function logins(): Builder
    {
        return (new LoginActivity())->setConnection(config('laravelusers.activity.connection'))->newQuery();
    }
}
