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
        abort_unless($this->supported(), 404);

        return $model::onlyTrashed();
    }

    public function exists(): bool
    {
        return $this->supported() && $this->query()->exists();
    }

    private function supported(): bool
    {
        return config('laravelusers.softDeletedEnabled', false)
            && in_array(SoftDeletes::class, class_uses_recursive(config('laravelusers.defaultUserModel')), true);
    }
}
