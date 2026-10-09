<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;

class PackageOperations
{
    public static function remember(string $id, array $record): void
    {
        $cache = ManagedPackages::cache();
        $cache->put('laravelusers.package.'.$id, $record, now()->addDay());
        $cache->put(self::actorKey((string) $record['actor']), $id, now()->addDay());
    }

    public static function latest(Model $actor): ?array
    {
        $id = ManagedPackages::cache()->get(self::actorKey((string) $actor->getKey()));

        return is_string($id) ? self::forActor($id, $actor) : null;
    }

    public static function forActor(string $id, Model $actor): ?array
    {
        $record = ManagedPackages::cache()->get('laravelusers.package.'.$id);
        if (!is_array($record) || ($record['actor'] ?? null) !== (string) $actor->getKey()) {
            return null;
        }
        $record = self::expireQueued($id, $record);
        $public = array_intersect_key($record, array_flip(['status', 'stage', 'package', 'operation', 'message', 'queued_at', 'started_at', 'updated_at']));

        return $public + ['id' => $id, 'status_url' => route('users.settings.packages.status', $id)];
    }

    public static function update(string $id, array $changes): void
    {
        $record = ManagedPackages::cache()->get('laravelusers.package.'.$id, []);
        ManagedPackages::cache()->put('laravelusers.package.'.$id, array_replace($record, $changes, ['updated_at' => now()->timestamp]), now()->addDay());
    }

    public static function overdue(array $record): bool
    {
        $timeout = max(30, (int) config('laravelusers.settings.packages.start_timeout', 120));

        return ($record['status'] ?? null) === 'queued' && isset($record['queued_at']) && now()->timestamp - $record['queued_at'] >= $timeout;
    }

    private static function expireQueued(string $id, array $record): array
    {
        if (!self::overdue($record)) {
            return $record;
        }
        $execution = ManagedPackages::cache()->lock('laravelusers.composer', 600);
        if (!$execution->get()) {
            return $record;
        }

        try {
            $record = ManagedPackages::cache()->get('laravelusers.package.'.$id, $record);
            if (($record['status'] ?? null) === 'queued') {
                self::update($id, ['status' => 'failed', 'stage' => 'queue', 'message' => trans('laravelusers::ui.package_worker_missing')]);
                $owner = $record['lock_owner'] ?? null;
                if (is_string($owner) && ManagedPackages::cache()->get('laravelusers.packages.owner') === $owner) {
                    ManagedPackages::cache()->forget('laravelusers.packages.owner');
                    ManagedPackages::cache()->restoreLock('laravelusers.packages', $owner)->release();
                }
            }

            return ManagedPackages::cache()->get('laravelusers.package.'.$id, $record);
        } finally {
            $execution->release();
        }
    }

    private static function actorKey(string $id): string
    {
        return 'laravelusers.package.actor.'.hash('sha256', config('laravelusers.defaultUserModel').'|'.$id);
    }
}
