<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Jobs\ChangeManagedPackage;
use jeremykenedy\laravelusers\Support\Frontend;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use Throwable;

class QueuePackageChange
{
    public function __construct(private readonly ManagedPackages $packages, private readonly Dispatcher $bus)
    {
    }

    public function handle(Model $actor, array $data): string
    {
        $this->packages->check($data['package'], $data['operation']);
        if (!$this->packages->queueReady()) {
            throw ValidationException::withMessages(['package' => 'Configure a persistent queue and shared cache before changing packages from settings. The sync queue is not supported.']);
        }
        $id = (string) Str::uuid();
        $lock = ManagedPackages::cache()->lock('laravelusers.packages', 900);
        if (!$lock->get()) {
            throw ValidationException::withMessages(['package' => 'Another package change is pending. Wait for it to finish before trying again.']);
        }
        ManagedPackages::cache()->put('laravelusers.packages.owner', $lock->owner(), now()->addMinutes(15));
        ManagedPackages::cache()->put('laravelusers.package.'.$id, ['actor' => (string) $actor->getKey(), 'status' => 'queued', 'package' => $data['package'], 'operation' => $data['operation'], 'setup' => !empty($data['setup']), 'migrate' => !empty($data['migrate']), 'framework' => Frontend::framework()], now()->addDay());

        try {
            $job = new ChangeManagedPackage($id, $actor->getKey(), $lock->owner());
            $job->onConnection(config('laravelusers.settings.packages.connection'))->onQueue(config('laravelusers.settings.packages.queue', 'default'));
            $this->bus->dispatch($job);
        } catch (Throwable $exception) {
            ManagedPackages::cache()->forget('laravelusers.packages.owner');
            $lock->release();
            ManagedPackages::cache()->forget('laravelusers.package.'.$id);

            throw $exception;
        }

        return $id;
    }
}
