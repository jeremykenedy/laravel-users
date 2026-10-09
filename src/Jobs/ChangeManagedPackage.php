<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\UserSettings;
use RuntimeException;
use Throwable;

class ChangeManagedPackage implements ShouldQueue
{
    use Queueable;

    public $tries = 1;

    public $timeout = 360;

    public $failOnTimeout = true;

    public function __construct(public readonly string $id, public readonly int|string $actorId, public readonly string $lockOwner)
    {
    }

    public function handle(ManagedPackages $packages, ComposerPackages $composer, UserSettings $settings): void
    {
        $record = ManagedPackages::cache()->get('laravelusers.package.'.$this->id);
        if (in_array($record['status'] ?? null, ['completed', 'failed'], true)) {
            return;
        }
        if (!$record) {
            $this->failed(new RuntimeException('Package operation expired.'));

            return;
        }

        $execution = ManagedPackages::cache()->lock('laravelusers.composer', 600);

        try {
            $this->authorize($packages, $settings);
            $packages->check($record['package'], $record['operation']);
            if (!$execution->get()) {
                throw ValidationException::withMessages(['package' => 'Another Composer operation is running. Wait for it to finish.']);
            }
            ManagedPackages::cache()->put('laravelusers.package.'.$this->id, array_replace($record, ['status' => 'running']), now()->addDay());
            $this->change($composer, $record);
            $message = $record['operation'] === 'install'
                ? 'Composer installation completed. Finish the package setup using the instructions below before enabling the integration. Restart queue workers after changing dependencies.'
                : 'Composer removal completed. Database tables and published files were retained. Review application references and restart queue workers.';
            ManagedPackages::cache()->put('laravelusers.package.'.$this->id, array_replace($record, ['status' => 'completed', 'message' => $message]), now()->addDay());
        } catch (Throwable $exception) {
            $this->failed($exception);
        } finally {
            $execution->release();
            $this->release();
        }
    }

    private function authorize(ManagedPackages $packages, UserSettings $settings): void
    {
        $settings->load();
        $userModel = config('laravelusers.defaultUserModel');
        $actor = (new $userModel())->newQuery()->find($this->actorId);
        if (!$actor || !$packages->allowed($actor)) {
            throw ValidationException::withMessages(['package' => 'Your package management access has changed. The operation was cancelled.']);
        }
        if (ManagedPackages::cache()->get('laravelusers.packages.owner') !== $this->lockOwner) {
            throw ValidationException::withMessages(['package' => 'The package operation expired. Submit it again from settings.']);
        }
    }

    private function change(ComposerPackages $composer, array $record): void
    {
        if (!$composer->changeFromSettings($record['operation'], ManagedPackages::PACKAGES[$record['package']])) {
            throw new RuntimeException('Composer could not complete the operation. Review the application logs and composer.json before retrying.');
        }
        if ($record['operation'] === 'install' && !empty($record['setup']) && !$composer->setup($record['package'], $record['framework'], !empty($record['migrate']), fn ($text) => null)) {
            throw new RuntimeException('Package installed, but setup failed. Review the application logs and rerun laravelusers:setup-package.');
        }
    }

    private function release(): void
    {
        if (ManagedPackages::cache()->get('laravelusers.packages.owner') === $this->lockOwner) {
            ManagedPackages::cache()->forget('laravelusers.packages.owner');
        }
        ManagedPackages::cache()->restoreLock('laravelusers.packages', $this->lockOwner)->release();
    }

    public function failed(Throwable $exception): void
    {
        $record = ManagedPackages::cache()->get('laravelusers.package.'.$this->id, []);
        $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->first() : 'Package change failed. Review the application logs and Composer files before retrying.';
        ManagedPackages::cache()->put('laravelusers.package.'.$this->id, array_replace($record, ['status' => 'failed', 'message' => $message]), now()->addDay());
        $this->release();
        report($exception);
    }
}
