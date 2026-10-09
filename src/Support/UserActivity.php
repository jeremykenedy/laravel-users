<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;
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

    public function listing(iterable $users, bool $includeDetails = false): array
    {
        $records = [];
        $keys = [];
        foreach ($users as $user) {
            $keys[$this->key($user)] = $user->getKey();
            $records[$user->getKey()] = ['online' => $this->isOnline($user), 'last_login_at' => null];
        }
        if (!$keys || !config('laravelusers.activity.login', false)) {
            return $records;
        }

        try {
            foreach ($this->logins()->whereIn('user_key', array_keys($keys))->get(['user_key', 'last_login_at', 'ip_address', 'device', 'os', 'browser']) as $login) {
                $records[$keys[$login->user_key]] += $this->loginDetails($login, $includeDetails);
                $records[$keys[$login->user_key]]['last_login_at'] = $login->last_login_at?->format('Y-m-d H:i:s');
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return $records;
    }

    private function loginDetails(LoginActivity $login, bool $includeDetails): array
    {
        return $includeDetails ? $login->only(['ip_address', 'device', 'os', 'browser']) : [];
    }

    public function touch(Model $user, Request $request, bool $logout = false): void
    {
        if (!config('laravelusers.activity.online', false) || !$request->hasSession()) {
            return;
        }

        try {
            $store = $this->cache->store(config('laravelusers.activity.cache_store'));
            $key = 'laravelusers:online:'.$this->key($user);
            $session = $this->presenceToken($request, $logout);
            if (!$session) {
                return;
            }
            $ttl = max(1, (int) config('laravelusers.activity.online_seconds', 300));
            $store->lock($key.':lock', 5)->block(1, fn () => $this->updatePresence($store, $key, $session, $ttl, $logout));
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function presenceToken(Request $request, bool $logout): ?string
    {
        $session = $request->session()->get('laravelusers.presence_token');
        if (!$session && !$logout) {
            $session = bin2hex(random_bytes(32));
            $request->session()->put('laravelusers.presence_token', $session);
        }

        return $session ?: null;
    }

    private function updatePresence(Repository $store, string $key, string $session, int $ttl, bool $logout): void
    {
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

    public function forget(Model $user, bool $forgetLogin = true): void
    {
        if ($forgetLogin && config('laravelusers.activity.login', false)) {
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
