<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Queue\WorkerOptions;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use jeremykenedy\laravelusers\Jobs\VerifyPackageWorker;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\PackageWorker;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

/**
 * PHPUnit requires public methods for these independent behavior and regression scenarios.
 * Integration fixtures exercise the framework types and optional providers used by this feature.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 * @SuppressWarnings("PHPMD.TooManyPublicMethods")
 */
class PackageWorkerTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cachePath = sys_get_temp_dir().'/laravelusers-worker-tests-'.bin2hex(random_bytes(8));
        config([
            'laravelusers.settings.packages.connection' => 'package-check',
            'laravelusers.settings.packages.queue'      => 'package-checks',
            'laravelusers.settings.packages.cache'      => 'package-check',
            'queue.connections.package-check'           => ['driver' => 'database', 'connection' => 'testing', 'table' => 'package_probe_jobs', 'queue' => 'default', 'retry_after' => 600],
            'queue.failed.driver'                       => null,
            'cache.stores.package-check'                => ['driver' => 'file', 'path' => $this->cachePath, 'lock_path' => $this->cachePath.'/locks'],
        ]);
        Schema::create('package_probe_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        File::deleteDirectory($this->cachePath);
        parent::tearDown();
    }

    public function test_verification_requires_a_round_trip_on_the_configured_queue_and_expires(): void
    {
        $this->composer();
        PackageWorker::probe();
        PackageWorker::probe();
        $this->assertFalse(PackageWorker::verified());
        $this->assertNull(PackageWorker::failure());
        $this->assertSame(1, DB::table('package_probe_jobs')->count());
        $this->assertSame('package-checks', DB::table('package_probe_jobs')->value('queue'));

        $this->work('default');
        $this->assertFalse(PackageWorker::verified());
        $this->work();

        $this->assertTrue(PackageWorker::verified());
        $this->assertNull(PackageWorker::failure());
        $this->assertSame(0, DB::table('package_probe_jobs')->count());
        PackageWorker::probe();
        $this->assertSame(0, DB::table('package_probe_jobs')->count());
        $this->travel(59)->seconds();
        $this->assertTrue(PackageWorker::verified());
        $this->travel(2)->seconds();
        $this->assertFalse(PackageWorker::verified());
    }

    public function test_direct_and_synchronous_execution_cannot_verify_a_worker(): void
    {
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('readiness');
        $this->app->instance(ComposerPackages::class, $composer);
        PackageWorker::probe();
        $job = $this->queuedProbe();

        $job->handle($composer);
        $this->assertFalse(PackageWorker::verified());
        $this->app->make(Dispatcher::class)->dispatchSync($job);
        $this->assertFalse(PackageWorker::verified());
    }

    public function test_sync_queues_and_process_local_cache_cannot_be_verified(): void
    {
        Bus::fake();
        config(['queue.connections.package-check.driver' => 'sync']);
        PackageWorker::probe();
        $this->assertFalse(PackageWorker::verified());
        config(['queue.connections.package-check.driver' => 'database', 'cache.stores.package-check.driver' => 'array']);
        PackageWorker::probe();
        $this->assertFalse(PackageWorker::verified());
        Bus::assertNothingDispatched();
    }

    public function test_a_worker_using_different_cache_storage_cannot_acknowledge_the_web_probe(): void
    {
        $this->composer();
        PackageWorker::probe();
        config(['cache.stores.package-check.path' => $this->cachePath.'/worker-only']);
        Cache::forgetDriver('package-check');

        $this->work();

        $this->assertFalse(PackageWorker::verified());
        config(['cache.stores.package-check.path' => $this->cachePath]);
        Cache::forgetDriver('package-check');
        $this->assertFalse(PackageWorker::verified());
    }

    public function test_changing_the_queue_or_connection_configuration_invalidates_verification(): void
    {
        $this->composer();
        PackageWorker::probe();
        $this->work();
        $this->assertTrue(PackageWorker::verified());

        config(['laravelusers.settings.packages.queue' => 'other-packages']);
        $this->assertFalse(PackageWorker::verified());
        config(['laravelusers.settings.packages.queue' => 'package-checks', 'queue.connections.package-check.retry_after' => 900]);
        $this->assertFalse(PackageWorker::verified());
    }

    public function test_duplicate_delivery_does_not_extend_a_verification_lease(): void
    {
        $this->composer(null, 2);
        PackageWorker::probe();
        $job = $this->queuedProbe();
        $this->work();
        $this->travel(59)->seconds();
        Queue::connection('package-check')->push($job, '', 'package-checks');

        $this->work();

        $this->assertTrue(PackageWorker::verified());
        $this->travel(2)->seconds();
        $this->assertFalse(PackageWorker::verified());
    }

    public function test_stale_deliveries_and_failure_callbacks_cannot_answer_a_new_probe(): void
    {
        $this->composer(null, 2);
        PackageWorker::probe();
        $old = $this->queuedProbe();
        $this->travel(61)->seconds();
        PackageWorker::probe();
        $new = $this->queuedProbe();
        $this->assertNotSame($old->nonce, $new->nonce);
        $this->assertSame(2, DB::table('package_probe_jobs')->count());

        $this->work();
        $old->failed(new RuntimeException('Private old worker failure.'));
        $this->assertFalse(PackageWorker::verified());
        $this->assertNull(PackageWorker::failure());
        $this->work();

        $this->assertTrue(PackageWorker::verified());
        $old->failed(new RuntimeException('Private old worker failure.'));
        $new->failed(new RuntimeException('Private late worker failure.'));
        $this->assertTrue(PackageWorker::verified());
        $this->assertNull(PackageWorker::failure());
    }

    public function test_worker_composer_failures_are_safe_and_prevent_verification(): void
    {
        foreach (['package_composer_missing', 'package_composer_manifest', 'package_composer_vendor', 'package_composer_application'] as $failure) {
            $this->composer('laravelusers::ui.'.$failure);
            PackageWorker::probe();

            $this->work();

            $this->assertFalse(PackageWorker::verified());
            $this->assertSame('laravelusers::ui.'.$failure, PackageWorker::failure());
            PackageWorker::probe();
            $this->assertSame(0, DB::table('package_probe_jobs')->count());
            $this->travel(61)->seconds();
        }
    }

    public function test_unexpected_readiness_results_cannot_expose_private_diagnostics(): void
    {
        $this->composer('Private Composer credentials and process output.');
        PackageWorker::probe();

        $this->work();

        $this->assertFalse(PackageWorker::verified());
        $this->assertSame('laravelusers::ui.package_requirements_not_verified', PackageWorker::failure());
    }

    public function test_failed_worker_probes_expire_and_can_be_retried_without_exposing_exceptions(): void
    {
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldReceive('readiness')->once()->andThrow(new RuntimeException('Private worker environment.'));
        $this->app->instance(ComposerPackages::class, $composer);
        PackageWorker::probe();

        $this->work();

        $this->assertFalse(PackageWorker::verified());
        $this->assertSame('laravelusers::ui.package_requirements_not_verified', PackageWorker::failure());
        $this->assertSame(0, DB::table('package_probe_jobs')->count());
        $this->travel(61)->seconds();
        $this->assertNull(PackageWorker::failure());
        $this->composer();
        PackageWorker::probe();
        $this->work();
        $this->assertTrue(PackageWorker::verified());
    }

    public function test_dispatch_failure_is_not_verified_and_does_not_flood_retries(): void
    {
        Bus::shouldReceive('dispatch')->once()->andThrow(new RuntimeException('Private queue connection.'));

        try {
            PackageWorker::probe();
            $this->fail('A failed queue dispatch must not appear successful.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Private queue connection.', $exception->getMessage());
        }

        $this->assertFalse(PackageWorker::verified());
        $this->assertSame('laravelusers::ui.package_requirements_not_verified', PackageWorker::failure());
        PackageWorker::probe();
    }

    private function composer(?string $failure = null, int $calls = 1): void
    {
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldReceive('readiness')->times($calls)->andReturn($failure);
        $this->app->instance(ComposerPackages::class, $composer);
    }

    private function work(string $queue = 'package-checks'): void
    {
        $options = new WorkerOptions();
        $options->sleep = 0;
        $this->app->make('queue.worker')->runNextJob('package-check', $queue, $options);
    }

    private function queuedProbe(): VerifyPackageWorker
    {
        $payload = json_decode(DB::table('package_probe_jobs')->latest('id')->value('payload'), true);

        return unserialize($payload['data']['command']);
    }
}
