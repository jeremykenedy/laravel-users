<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Support\AccountPreferences;
use jeremykenedy\laravelusers\Support\EmailChanges;
use RuntimeException;

class DeleteAccount
{
    public function __construct(private readonly SendGoodbye $goodbye, private readonly EmailChanges $emails)
    {
    }

    public function handle(Model $user, array $data): void
    {
        $user->getConnection()->transaction(function () use ($user, $data) {
            $locked = $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            abort_unless(AccountPreferences::editable($locked), 403);
            if (!Hash::check($data['current_password'], $locked->getAuthPassword())) {
                throw ValidationException::withMessages(['current_password' => trans('laravelusers::ui.account_password_invalid')]);
            }
            $this->emails->cancel($locked);
            if (!$locked->delete()) {
                throw new RuntimeException('Account deletion was rejected.');
            }
            $this->goodbye->handle($locked, []);
        }, 3);
    }
}
