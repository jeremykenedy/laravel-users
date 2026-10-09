<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Models\AccountLink;
use JsonException;
use RuntimeException;

/**
 * Keeps the encrypted single-use link lifecycle together; each operation has its own method complexity checks.
 *
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class AccountLinks
{
    public function __construct(private readonly Encrypter $encrypter, private readonly DeletedUsers $deleted)
    {
    }

    public function issue(Model $user, array $actions, int $minutes): array
    {
        $maximum = max(1, min(525600, (int) config('laravelusers.account_links.max_expire', 43200)));
        if (!$this->available() || $minutes < 0 || $minutes > $maximum || ($minutes === 0 && !config('laravelusers.account_links.allow_never_expire', true))) {
            throw ValidationException::withMessages(['account_duration' => trans('laravelusers::ui.account_links_unavailable')]);
        }

        return $this->createLinks($user, $actions, $minutes === 0 ? null : now()->addMinutes($minutes)->timestamp);
    }

    public function issueForCleanup(Model $user, array $actions): array
    {
        if (!$this->available() || !config('laravelusers.cleanup.enabled', false)) {
            throw ValidationException::withMessages(['account_duration' => trans('laravelusers::ui.account_links_unavailable')]);
        }
        $expires = DeletedUserRetention::expiresAt($user)->timestamp;

        return $expires > now()->timestamp ? $this->createLinks($user, $actions, $expires) : [];
    }

    private function createLinks(Model $user, array $actions, ?int $expires): array
    {
        return $user->getConnection()->transaction(function () use ($user, $actions, $expires) {
            $recipient = $this->deleted->query()->whereKey($user->getKey())->lockForUpdate()->first();
            if (!$recipient || $this->fingerprint($recipient) !== $this->fingerprint($user)) {
                throw ValidationException::withMessages(['ids' => trans('laravelusers::ui.email_invalid_selection')]);
            }
            $links = [];
            foreach ($actions as $action) {
                if (!in_array($action, ['restore', 'force_delete'], true) || !config('laravelusers.account_links.'.$action, true)) {
                    throw ValidationException::withMessages(['account_links' => trans('laravelusers::ui.account_links_unavailable')]);
                }
                $secret = bin2hex(random_bytes(32));
                $id = (string) Str::uuid();
                $this->query()->create([
                    'id'                  => $id, 'user_type' => get_class($recipient), 'user_id' => (string) $recipient->getKey(),
                    'action'              => $action, 'token_hash' => hash('sha256', $secret),
                    'deleted_fingerprint' => $this->fingerprint($recipient), 'expires_at' => $expires,
                ]);
                $token = rtrim(strtr($this->encrypter->encrypt(json_encode(['id' => $id, 'secret' => $secret], JSON_THROW_ON_ERROR), false), '+/', '-_'), '=');
                $links[$action] = route('users.account-link', ['token' => $token]);
            }

            return $links;
        });
    }

    public function inspect(string $token): ?AccountLink
    {
        $credentials = $this->decode($token);
        if (!$credentials || !$this->available()) {
            return null;
        }
        $link = $this->query()->find($credentials['id']);
        if (!$this->valid($link, $credentials['secret'])) {
            return null;
        }
        $user = $this->deleted->query()->whereKey($link->user_id)->first();

        return $user && hash_equals($link->deleted_fingerprint, $this->fingerprint($user)) ? $link : null;
    }

    public function consume(string $token): ?string
    {
        $credentials = $this->decode($token);
        $link = $credentials && $this->available() ? $this->query()->find($credentials['id']) : null;
        if (!$this->valid($link, $credentials['secret'] ?? '')) {
            return null;
        }

        return $link->getConnection()->transaction(fn () => $this->consumeLocked($link, $credentials['secret']), 3);
    }

    private function consumeLocked(AccountLink $link, string $secret): ?string
    {
        $user = $this->deleted->query()->whereKey($link->user_id)->lockForUpdate()->first();
        $locked = $this->query()->whereKey($link->id)->lockForUpdate()->first();
        if (!$user || !$this->valid($locked, $secret) || !hash_equals($locked->deleted_fingerprint, $this->fingerprint($user))) {
            return null;
        }
        $claimed = $this->query()->whereKey($locked->id)->whereNull('consumed_at')->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()->timestamp))->update(['consumed_at' => now()->timestamp]);
        if ($claimed !== 1) {
            return null;
        }
        $result = $locked->action === 'restore' ? $user->restore() : $user->forceDelete();
        if (!$result) {
            throw new RuntimeException('The account action could not be completed.');
        }
        $this->invalidate($user);

        return $locked->action;
    }

    public function invalidate(Model $user): void
    {
        if (get_class($user) === config('laravelusers.defaultUserModel') && $this->available()) {
            $this->query()->where('user_type', get_class($user))->where('user_id', (string) $user->getKey())->whereNull('consumed_at')->update(['consumed_at' => now()->timestamp]);
        }
    }

    public function prune(): int
    {
        return $this->available() ? $this->query()->where('expires_at', '<=', now()->timestamp)->delete() : 0;
    }

    private function valid(?AccountLink $link, string $secret): bool
    {
        return $link && !$link->consumed_at && ($link->expires_at === null ? config('laravelusers.account_links.allow_never_expire', true) : $link->expires_at > now()->timestamp)
            && $link->user_type === config('laravelusers.defaultUserModel')
            && in_array($link->action, ['restore', 'force_delete'], true)
            && config('laravelusers.account_links.'.$link->action, true)
            && hash_equals($link->token_hash, hash('sha256', $secret));
    }

    private function decode(string $token): ?array
    {
        if (strlen($token) > 4096 || !preg_match('/^[A-Za-z0-9_-]+$/D', $token)) {
            return null;
        }

        try {
            $data = json_decode($this->encrypter->decrypt(strtr($token, '-_', '+/'), false), true, 4, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException $exception) {
            return null;
        }

        return is_array($data) && $this->validCredentials($data) ? $data : null;
    }

    private function validCredentials(array $data): bool
    {
        return is_string($data['id'] ?? null) && Str::isUuid($data['id'])
            && is_string($data['secret'] ?? null) && preg_match('/^[a-f0-9]{64}$/D', $data['secret']);
    }

    private function fingerprint(Model $user): string
    {
        return hash('sha256', json_encode([get_class($user), (string) $user->getKey(), $user->getRawOriginal($user->getDeletedAtColumn()), $user->email], JSON_THROW_ON_ERROR));
    }

    private function available(): bool
    {
        $model = config('laravelusers.defaultUserModel');
        $user = new $model();

        return config('laravelusers.account_links.enabled', false) && config('laravelusers.softDeletedEnabled', false)
            && in_array(SoftDeletes::class, class_uses_recursive($user), true)
            && $user->getConnection()->getSchemaBuilder()->hasTable((new AccountLink())->getTable());
    }

    private function query(): Builder
    {
        $model = config('laravelusers.defaultUserModel');
        $link = new AccountLink();
        $link->setConnection((new $model())->getConnectionName());

        return $link->newQuery();
    }
}
