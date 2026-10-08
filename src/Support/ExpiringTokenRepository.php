<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Closure;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use JsonException;

class ExpiringTokenRepository implements TokenRepositoryInterface
{
    private ?int $minutes = null;

    public function __construct(private readonly TokenRepositoryInterface $repository, private readonly Closure $forExpiry, private readonly Encrypter $encrypter, private readonly string $provider, private readonly int $retention)
    {
    }

    public function withExpiry(int $minutes, Closure $callback): mixed
    {
        $previous = $this->minutes;
        $this->minutes = $minutes;

        try {
            return $callback();
        } finally {
            $this->minutes = $previous;
        }
    }

    public function create(CanResetPassword $user): string
    {
        if ($this->minutes === null) {
            return $this->repository->create($user);
        }
        $token = ($this->forExpiry)($this->minutes)->create($user);
        $payload = json_encode(['token' => $token, 'minutes' => $this->minutes, 'provider' => $this->provider], JSON_THROW_ON_ERROR);

        return 'lu1.'.rtrim(strtr($this->encrypter->encrypt($payload, false), '+/', '-_'), '=');
    }

    public function exists(CanResetPassword $user, $token): bool
    {
        if (!is_string($token) || !str_starts_with($token, 'lu1.')) {
            return $this->repository->exists($user, $token);
        }
        if (strlen($token) > 4096 || !preg_match('/^lu1\.[A-Za-z0-9_-]+$/D', $token)) {
            return false;
        }

        try {
            $payload = substr($token, 4);
            $payload = strtr($payload, '-_', '+/').str_repeat('=', (4 - strlen($payload) % 4) % 4);
            $data = json_decode($this->encrypter->decrypt($payload, false), true, 4, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException $exception) {
            return false;
        }
        if (!is_array($data) || !is_string($data['token'] ?? null) || !is_int($data['minutes'] ?? null) || $data['minutes'] < 0 || $data['minutes'] > 525600 || ($data['provider'] ?? null) !== $this->provider
            || ($data['minutes'] === 0 && !config('laravelusers.emails.reset_allow_never_expire', true))) {
            return false;
        }

        return ($this->forExpiry)($data['minutes'])->exists($user, $data['token']);
    }

    public function recentlyCreatedToken(CanResetPassword $user): bool
    {
        return $this->repository->recentlyCreatedToken($user);
    }

    public function delete(CanResetPassword $user): void
    {
        $this->repository->delete($user);
    }

    public function deleteExpired(): void
    {
        ($this->forExpiry)($this->retention)->deleteExpired();
    }
}
