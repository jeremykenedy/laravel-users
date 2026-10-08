<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Support\DeletedUsers;

class BulkUsers
{
    public function __construct(private readonly DeletedUsers $deleted)
    {
    }

    public function handle(array $data): void
    {
        $model = config('laravelusers.defaultUserModel');
        $query = $data['action'] === 'delete' ? $model::query() : $this->deleted->query();
        $query->getModel()->getConnection()->transaction(function () use ($query, $data) {
            $users = $query->whereKey($data['ids'])->lockForUpdate()->get();
            if ($users->count() !== count($data['ids']) || $users->contains(fn ($user) => Auth::check() && (string) $user->getKey() === (string) Auth::id())) {
                throw ValidationException::withMessages(['ids' => trans('laravelusers::ui.invalid_selection')]);
            }
            $method = ['delete' => 'delete', 'restore' => 'restore', 'force_delete' => 'forceDelete'][$data['action']];
            foreach ($users as $user) {
                $user->$method();
            }
        });
    }
}
