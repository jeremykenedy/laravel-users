<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\Collection;

class SearchUsers
{
    public function handle(string $term): Collection
    {
        $model = config('laravelusers.defaultUserModel');

        return $model::query()->when(config('laravelusers.rolesEnabled', false), fn ($query) => $query->with('roles'))
            ->where(fn ($query) => $query->where('id', 'like', $term.'%')->orWhere('name', 'like', $term.'%')->orWhere('email', 'like', $term.'%'))
            ->get();
    }
}
