<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeletedUsers
{
    public function query(): Builder
    {
        $model = config('laravelusers.defaultUserModel');
        abort_unless(config('laravelusers.softDeletedEnabled', false) && in_array(SoftDeletes::class, class_uses_recursive($model), true), 404);

        return $model::onlyTrashed();
    }
}
