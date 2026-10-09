<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use jeremykenedy\laravelusers\Support\DeletedUserRetention;
use jeremykenedy\laravelusers\Support\DeletedUsers;
use jeremykenedy\laravelusers\Support\UserSettings;
use RuntimeException;

class CleanupDeletedUsers
{
    public function __construct(private readonly UserSettings $settings, private readonly DeletedUsers $deleted)
    {
    }

    public function handle(): int
    {
        $this->settings->load();
        $model = config('laravelusers.defaultUserModel');
        if (!config('laravelusers.cleanup.enabled', false) || !config('laravelusers.softDeletedEnabled', false) || !in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            return 0;
        }
        $cutoff = DeletedUserRetention::cutoff();
        $lock = Cache::lock('laravelusers.deleted-cleanup', 600);
        if (!$lock->get()) {
            return 0;
        }
        $count = 0;

        try {
            $query = $this->deleted->query();
            $column = $query->getModel()->getDeletedAtColumn();
            $query->where($column, '<=', $cutoff)->chunkById(100, function ($users) use ($cutoff, $column, &$count) {
                foreach ($users as $user) {
                    $removed = $user->getConnection()->transaction(function () use ($user, $cutoff, $column) {
                        $deleted = $this->deleted->query()->whereKey($user->getKey())->where($column, '<=', $cutoff)->lockForUpdate()->first();
                        if (!$deleted) {
                            return false;
                        }
                        if (DeletedUserRetention::expiresAt($deleted)->isFuture()) {
                            return false;
                        }
                        if (!$deleted->forceDelete()) {
                            throw new RuntimeException('Permanent deletion was rejected. Cleanup stopped.');
                        }

                        return true;
                    }, 3);
                    $count += (int) $removed;
                }
            });
        } finally {
            $lock->release();
        }

        return $count;
    }
}
