<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EmailRecipients
{
    public function __construct(private readonly DeletedUsers $deleted)
    {
    }

    public function get(array $data): Collection
    {
        $model = config('laravelusers.defaultUserModel');
        $users = (!empty($data['deleted']) ? $this->deleted->query() : $model::query())->whereKey($data['ids'])->get();
        if ($users->count() !== count($data['ids'])) {
            throw ValidationException::withMessages(['ids' => trans('laravelusers::ui.email_invalid_selection')]);
        }
        foreach ($users as $user) {
            if (Gate::getPolicyFor($user)) {
                Gate::authorize('update', $user);
            }
        }

        return $users;
    }
}
