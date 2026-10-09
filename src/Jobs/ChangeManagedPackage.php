<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Validation\ValidationException;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageOperations;
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
        $claim = ManagedPackages::cache()->lock('laravelusers.package.claim.'.$this->id, 600);
        if (!$claim->get()) {
            return;
        }
        $record = ManagedPackages::cache()->get('laravelusers.package.'.$this->id);
        if (in_array($record['status'] ?? null, ['running', 'completed', 'failed'], true)) {
            $claim->release();

            return;
        }
        if (!$record) {
            $this->failed(new RuntimeException('Package operation expired.'));
            $claim->release();

            return;
        }

        $execution = ManagedPackages::cache()->lock('laravelusers.composer', 600);

        try {
            if (!$execution->get()) {
                throw ValidationException::withMessages(['package' => 'Another Composer operation is running. Wait for it to finish.']);
            }
            $record = ManagedPackages::cache()->get('laravelusers.package.'.$this->id);
            if (!is_array($record) || ($record['status'] ?? null) !== 'queued') {
                return;
            }
            if (PackageOperations::overdue($record)) {
                throw ValidationException::withMessages(['package' => trans('laravelusers::ui.package_worker_missing')]);
            }
            $this->authorize($packages, $settings);
            $packages->check($record['package'], $record['operation']);
            $message = match ($record['operation']) {
                'install'   => 'package_installing',
                'configure' => 'package_configuring',
                default     => 'package_removing',
            };
            PackageOperations::update($this->id, ['status' => 'running', 'stage' => $record['operation'] === 'configure' ? 'setup' : 'composer', 'started_at' => now()->timestamp, 'message' => trans('laravelusers::ui.'.$message)]);
            $this->change($composer, $record);
            $message = $record['operation'] === 'configure' ? 'Package setup completed.' : ($record['operation'] === 'install'
                ? (!empty($record['setup']) ? 'Package installed and setup completed. Restart remaining application workers after changing dependencies.' : 'Composer installation completed. Finish the package setup using the instructions below before enabling the integration. Restart remaining application workers after changing dependencies.')
                : 'Composer removal completed. Database tables and published files were retained. Review application references and restart remaining application workers.');
            PackageOperations::update($this->id, ['status' => 'completed', 'stage' => 'completed', 'message' => $message]);
        } catch (Throwable $exception) {
            $this->failed($exception);
        } finally {
            $execution->release();
            $this->release();
            $claim->release();
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
        if ($record['operation'] !== 'configure' && !$composer->changeFromSettings($record['operation'], ManagedPackages::PACKAGES[$record['package']])) {
            throw new RuntimeException('Composer could not complete the operation. Review the application logs and composer.json before retrying.');
        }
        if (in_array($record['operation'], ['install', 'configure'], true) && !empty($record['setup'])) {
            PackageOperations::update($this->id, ['stage' => 'setup', 'message' => trans('laravelusers::ui.package_configuring')]);
            if (!$composer->setup($record['package'], $record['framework'], !empty($record['migrate']), fn () => null)) {
                throw new RuntimeException('Package setup failed. Review the application logs before retrying setup from settings.');
            }
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
        if (in_array($record['status'] ?? null, ['completed', 'failed'], true)) {
            return;
        }
        $message = $exception instanceof ValidationException ? collect($exception->errors())->flatten()->first() : (($record['stage'] ?? '') === 'setup' ? 'Package setup failed. Review the application logs, then use Complete setup in settings before enabling the integration.' : 'Package change failed. Review the application logs and Composer files before retrying.');
        if (isset($record['actor'])) {
            PackageOperations::update($this->id, ['status' => 'failed', 'message' => $message]);
        }
        $this->release();
        report($exception);
    }
}
