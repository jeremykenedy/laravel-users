<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\AppearancePreferences;
use jeremykenedy\laravelusers\Support\AvatarPreferences;
use jeremykenedy\laravelusers\Support\EmailChanges;
use RuntimeException;

class UpdateAccount
{
    public function __construct(private readonly EmailChanges $emails)
    {
    }

    public function handle(Model $user, array $data): void
    {
        $user->getConnection()->transaction(function () use ($user, $data) {
            $locked = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            abort_unless(AccountPreferences::editable($locked), 403);
            if (in_array($data['section'], ['email', 'password'], true) && !Hash::check($data['current_password'], $locked->getAuthPassword())) {
                throw ValidationException::withMessages(['current_password' => trans('laravelusers::ui.account_password_invalid')]);
            }
            if ($data['section'] === 'profile') {
                $locked->setAttribute(config('laravelusers.account.username_column', 'name'), $data['username']);
                $column = config('laravelusers.account.name_column');
                if ($column) {
                    $locked->setAttribute($column, $data['full_name']);
                } else {
                    AccountPreferences::saveName($locked, $data['full_name']);
                }
                $this->save($locked);
            } elseif ($data['section'] === 'appearance') {
                AvatarPreferences::save($locked, $data);
                AppearancePreferences::save($locked, $data);
            } elseif ($data['section'] === 'password') {
                $locked->password = Hash::make($data['password']);
                $locked->setRememberToken(Str::random(60));
                $this->save($locked);
                $this->emails->cancel($locked);
            } elseif ($data['section'] === 'email' && $data['email'] !== $locked->email) {
                $this->emails->request($locked, $data['email']);
            }
        }, 3);
    }

    private function save(Model $user): void
    {
        if (!$user->save()) {
            throw new RuntimeException('Account changes were rejected.');
        }
    }
}
