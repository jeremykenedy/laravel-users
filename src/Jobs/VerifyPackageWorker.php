<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\SyncJob;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\PackageWorker;
use Throwable;

class VerifyPackageWorker implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public $tries = 1;

    public $timeout = 10;

    public $failOnTimeout = true;

    public function __construct(public readonly string $nonce, public readonly string $context)
    {
    }

    public function handle(ComposerPackages $composer): void
    {
        if (!$this->job || $this->job instanceof SyncJob || $this->job->getConnectionName() !== $this->connection) {
            return;
        }
        PackageWorker::acknowledge($this->nonce, $this->context, $composer->readiness());
    }

    /**
     * Laravel supplies the failed job exception to this callback.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function failed(Throwable $exception): void
    {
        PackageWorker::acknowledge($this->nonce, $this->context, 'laravelusers::ui.package_requirements_not_verified');
    }
}
