<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Contracts\Auth\Factory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use JsonException;

class ImpersonationSession
{
    public const KEY = 'laravelusers.impersonation';

    public function __construct(private Factory $auth, private Encrypter $encrypter)
    {
    }

    public function begin(Request $request, Model $actor, Model $target): void
    {
        $guard = config('auth.defaults.guard', 'web');
        abort_unless($this->auth->guard($guard) instanceof StatefulGuard, 409);
        abort_unless($actor::class === config('laravelusers.defaultUserModel') && $target::class === $actor::class, 403);
        $now = now()->getTimestamp();
        $minutes = max(1, min(1440, (int) config('laravelusers.impersonation.timeout', 60)));
        $state = [
            'actor_id'   => (string) $actor->getKey(),
            'actor_name' => (string) $actor->getAttribute('name'),
            'target_id'  => (string) $target->getKey(),
            'model'      => config('laravelusers.defaultUserModel'),
            'guard'      => $guard,
            'return_to'  => $this->returnPath($request),
            'started_at' => $now,
            'expires_at' => $now + $minutes * 60,
        ];
        $state['proof'] = $this->encrypter->encrypt(json_encode($state, JSON_THROW_ON_ERROR), false);
        $request->session()->put(self::KEY, $state);
        $this->auth->guard($guard)->login($target);
        $this->rotate($request);
    }

    public function read(Request $request): ?array
    {
        $state = $request->session()->get(self::KEY);
        if (!is_array($state) || !is_string($state['proof'] ?? null)) {
            return null;
        }
        $proof = $state['proof'];
        unset($state['proof']);

        try {
            $sealed = $this->encrypter->decrypt($proof, false);
            if (!is_string($sealed) || !hash_equals($sealed, json_encode($state, JSON_THROW_ON_ERROR))) {
                return null;
            }
        } catch (DecryptException|JsonException) {
            return null;
        }

        return $state;
    }

    public function actor(array $state): ?Model
    {
        $model = config('laravelusers.defaultUserModel');

        return ($state['model'] ?? null) === $model ? $model::query()->find($state['actor_id']) : null;
    }

    public function target(array $state): ?Model
    {
        $guard = $state['guard'] ?? null;
        if ($guard !== config('auth.defaults.guard', 'web') || !$this->auth->guard($guard) instanceof StatefulGuard) {
            return null;
        }
        $user = $this->auth->guard($guard)->user();

        return $user instanceof Model && $user::class === ($state['model'] ?? null) && (string) $user->getKey() === ($state['target_id'] ?? null) ? $user : null;
    }

    public function restore(Request $request, array $state, Model $actor): string
    {
        $this->auth->guard($state['guard'])->login($actor);
        $request->session()->forget(self::KEY);
        $this->rotate($request);

        return $this->safeReturnPath($state['return_to']);
    }

    public function invalidate(Request $request): void
    {
        $guard = $this->auth->guard();
        if ($guard instanceof StatefulGuard) {
            $guard->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function rotate(Request $request): void
    {
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }

    private function returnPath(Request $request): string
    {
        $referer = (string) $request->headers->get('referer');
        $path = parse_url($referer, PHP_URL_PATH);
        $query = parse_url($referer, PHP_URL_QUERY);
        $returnTo = is_string($path) && is_string($query) ? $path.'?'.mb_substr($query, 0, 2048) : $path;

        return $this->safeReturnPath($returnTo);
    }

    private function safeReturnPath(mixed $path): string
    {
        return is_string($path)
            && str_starts_with($path, '/')
            && !str_starts_with($path, '//')
            && preg_match('/[\r\n\\\\]/', $path) !== 1
            ? $path
            : route('users');
    }
}
