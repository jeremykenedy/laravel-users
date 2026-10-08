<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Models\EmailChange;
use jeremykenedy\laravelusers\Notifications\ConfirmEmailChange;
use JsonException;

class EmailChanges
{
    public function __construct(private readonly Encrypter $encrypter, private readonly Dispatcher $notifications)
    {
    }

    public function request(Model $user, string $email): void
    {
        abort_unless($this->available($user), 409, trans('laravelusers::ui.account_migration_required'));
        $minutes = max(1, min(10080, (int) config('laravelusers.account.email_expire', 1440)));
        $secrets = ['old' => bin2hex(random_bytes(32)), 'new' => bin2hex(random_bytes(32))];
        $id = (string) Str::uuid();
        $this->query($user)->where('user_key', AvatarPreferences::key($user))->delete();
        $this->query($user)->create([
            'id'             => $id, 'user_key' => AvatarPreferences::key($user), 'old_email' => $user->email, 'new_email' => $email,
            'old_token_hash' => hash('sha256', $secrets['old']), 'new_token_hash' => hash('sha256', $secrets['new']),
            'fingerprint'    => $this->fingerprint($user), 'expires_at' => now()->addMinutes($minutes)->timestamp,
        ]);
        foreach ($secrets as $side => $secret) {
            $token = rtrim(strtr($this->encrypter->encrypt(json_encode(['id' => $id, 'side' => $side, 'secret' => $secret], JSON_THROW_ON_ERROR), false), '+/', '-_'), '=');
            $recipient = (new AnonymousNotifiable())->route('mail', $side === 'old' ? $user->email : $email);
            $this->notifications->send($recipient, new ConfirmEmailChange($user->name, route('users.account.email.confirm', ['token' => $token]), $side, $minutes));
        }
    }

    public function inspect(Model $user, string $token): ?EmailChange
    {
        $credentials = $this->decode($token);
        $change = $credentials && $this->available($user) ? $this->query($user)->find($credentials['id']) : null;

        return config('laravelusers.account.email', true) && AccountPreferences::editable($user) && $this->valid($user, $change, $credentials) ? $change : null;
    }

    public function confirm(Model $user, string $token): ?string
    {
        $credentials = $this->decode($token);
        if (!$credentials || !$this->available($user) || !config('laravelusers.account.email', true)) {
            return null;
        }

        return $user->getConnection()->transaction(function () use ($user, $credentials) {
            $locked = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->first();
            $change = $this->query($user)->whereKey($credentials['id'])->lockForUpdate()->first();
            if (!$locked || !AccountPreferences::editable($locked) || !$this->valid($locked, $change, $credentials)) {
                return null;
            }
            $column = $credentials['side'].'_confirmed_at';
            if ($this->query($user)->whereKey($change->id)->whereNull($column)->update([$column => now()->timestamp]) !== 1) {
                return null;
            }
            $change->$column = now()->timestamp;
            if (!$change->old_confirmed_at || !$change->new_confirmed_at) {
                return 'pending';
            }
            $duplicates = $locked->newQueryWithoutScopes()->where('email', $change->new_email)->where($locked->getKeyName(), '!=', $locked->getKey())->exists();
            if ($duplicates) {
                throw ValidationException::withMessages(['email' => trans('laravelusers::ui.account_email_taken')]);
            }
            $locked->email = $change->new_email;
            if ($locked->getConnection()->getSchemaBuilder()->hasColumn($locked->getTable(), 'email_verified_at')) {
                $locked->email_verified_at = now();
            }

            try {
                if (!$locked->save()) {
                    throw new \RuntimeException('Email change was rejected.');
                }
            } catch (QueryException $exception) {
                if (in_array($exception->errorInfo[0] ?? null, ['23000', '23505'], true)) {
                    throw ValidationException::withMessages(['email' => trans('laravelusers::ui.account_email_taken')]);
                }

                throw $exception;
            }
            $change->delete();

            return 'complete';
        }, 3);
    }

    public function pending(Model $user): ?EmailChange
    {
        $change = $this->available($user) ? $this->query($user)->where('user_key', AvatarPreferences::key($user))->where('expires_at', '>', now()->timestamp)->first() : null;

        return $change && hash_equals($change->fingerprint, $this->fingerprint($user)) ? $change : null;
    }

    public function cancel(Model $user): void
    {
        if ($this->available($user)) {
            $this->query($user)->where('user_key', AvatarPreferences::key($user))->delete();
        }
    }

    private function valid(Model $user, ?EmailChange $change, ?array $credentials): bool
    {
        return $change && $credentials && $change->user_key === AvatarPreferences::key($user) && $change->expires_at > now()->timestamp
            && !$change->{$credentials['side'].'_confirmed_at'} && hash_equals($change->fingerprint, $this->fingerprint($user))
            && hash_equals($change->{$credentials['side'].'_token_hash'}, hash('sha256', $credentials['secret']));
    }

    private function decode(string $token): ?array
    {
        if (strlen($token) > 4096 || !preg_match('/^[A-Za-z0-9_-]+$/D', $token)) {
            return null;
        }

        try {
            $value = json_decode($this->encrypter->decrypt(strtr($token, '-_', '+/'), false), true, 4, JSON_THROW_ON_ERROR);
        } catch (DecryptException|JsonException $exception) {
            return null;
        }

        return is_array($value) && isset($value['id'], $value['side'], $value['secret']) && is_string($value['id']) && Str::isUuid($value['id'])
            && in_array($value['side'], ['old', 'new'], true) && is_string($value['secret']) && preg_match('/^[a-f0-9]{64}$/D', $value['secret']) ? $value : null;
    }

    private function fingerprint(Model $user): string
    {
        return hash('sha256', json_encode([AvatarPreferences::key($user), $user->email, $user->getAuthPassword()], JSON_THROW_ON_ERROR));
    }

    private function available(Model $user): bool
    {
        return $user->getConnection()->getSchemaBuilder()->hasTable((new EmailChange())->getTable());
    }

    private function query(Model $user): Builder
    {
        return (new EmailChange())->setConnection($user->getConnectionName())->newQuery();
    }
}
