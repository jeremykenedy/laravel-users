<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use jeremykenedy\laravelusers\Jobs\ChangeManagedPackage;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\PackageOperations;
use jeremykenedy\laravelusers\Support\UserSettings;
use jeremykenedy\laravelusers\Test\Fixtures\User;
use jeremykenedy\laravelusers\Test\TestCase;
use RuntimeException;

class PackageOperationSecurityTest extends TestCase
{
    private string $cachePath;

    private ManagedPackages $packages;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cachePath = sys_get_temp_dir().'/laravelusers-operation-security-'.bin2hex(random_bytes(8));
        config([
            'laravelusers.settings.enabled'          => true,
            'laravelusers.settings.packages.enabled' => true,
            'queue.default'                          => 'database',
            'queue.connections.database.driver'      => 'database',
            'queue.connections.database.retry_after' => 600,
            'cache.default'                          => 'file',
            'cache.stores.file.driver'               => 'file',
            'cache.stores.file.path'                 => $this->cachePath,
            'cache.stores.file.lock_path'            => $this->cachePath.'/locks',
        ]);
        Cache::forgetDriver('file');
        Gate::define('manage-laravelusers-settings', fn () => true);
        Gate::define('manage-laravelusers-packages', fn () => true);
        Bus::fake();
        $this->packages = \Mockery::mock(ManagedPackages::class)->makePartial();
        $this->packages->shouldReceive('installed')->andReturnFalse();
        $this->app->instance(ManagedPackages::class, $this->packages);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->cachePath);
        parent::tearDown();
    }

    public function test_duplicate_delivery_cannot_fail_or_unlock_a_running_operation(): void
    {
        [$job] = $this->queue();
        $observed = [];
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldReceive('changeFromSettings')->once()->andReturnUsing(function () use ($job, &$observed) {
            $duplicateComposer = \Mockery::mock(ComposerPackages::class);
            $duplicateComposer->shouldNotReceive('changeFromSettings');
            (clone $job)->handle($this->packages, $duplicateComposer, new UserSettings());
            $observed = [
                'status' => Cache::get('laravelusers.package.'.$job->id)['status'],
                'owner'  => Cache::get('laravelusers.packages.owner'),
                'locked' => $this->operationLocked(),
            ];

            return true;
        });

        $job->handle($this->packages, $composer, new UserSettings());

        $this->assertSame('running', $observed['status']);
        $this->assertSame($job->lockOwner, $observed['owner']);
        $this->assertTrue($observed['locked']);
        $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);
    }

    public function test_late_failure_callbacks_cannot_replace_a_completed_operation(): void
    {
        [$job] = $this->queue();
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldReceive('changeFromSettings')->once()->andReturnTrue();
        $job->handle($this->packages, $composer, new UserSettings());
        $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);

        $job->failed(new RuntimeException('Private process diagnostics.'));

        $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);
    }

    public function test_duplicate_delivery_during_claim_cannot_cancel_the_first_worker(): void
    {
        [$job] = $this->queue();
        $checking = false;
        $this->packages->shouldReceive('check')->andReturnUsing(function () use ($job, &$checking) {
            if ($checking) {
                return;
            }
            $checking = true;
            $duplicate = \Mockery::mock(ComposerPackages::class);
            $duplicate->shouldNotReceive('changeFromSettings');
            (clone $job)->handle($this->packages, $duplicate, new UserSettings());
        });
        $composer = \Mockery::mock(ComposerPackages::class);
        $ownedWhenComposerStarted = false;
        $composer->shouldReceive('changeFromSettings')->once()->andReturnUsing(function () use ($job, &$ownedWhenComposerStarted) {
            $ownedWhenComposerStarted = Cache::get('laravelusers.packages.owner') === $job->lockOwner
                && $this->operationLocked();

            return true;
        });

        $job->handle($this->packages, $composer, new UserSettings());

        $this->assertTrue($ownedWhenComposerStarted, 'The first worker must retain its operation ownership through the claim.');
        $this->assertSame('completed', Cache::get('laravelusers.package.'.$job->id)['status']);
    }

    public function test_status_only_exposes_public_operation_fields(): void
    {
        [$job, $url] = $this->queue();
        $key = 'laravelusers.package.'.$job->id;
        Cache::put($key, array_replace(Cache::get($key), ['lock_owner' => 'private-lock-owner', 'owner' => 'private-owner', 'composer_output' => 'private-process-output', 'exception' => 'private-exception-detail']), now()->addDay());

        $response = $this->getJson($url)->assertOk()->assertJsonPath('status', 'queued');

        foreach (['actor', 'lock_owner', 'owner', 'composer_output', 'exception'] as $field) {
            $this->assertArrayNotHasKey($field, $response->json());
        }
        $response->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_expired_queued_operation_cannot_run_when_delivered_later(): void
    {
        $this->requireProgress();
        [$job, $url] = $this->queue();
        $this->makeStale($job);
        $this->getJson($url)->assertOk()->assertJsonPath('status', 'failed');
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('changeFromSettings');

        $job->handle($this->packages, $composer, new UserSettings());

        $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
        $this->assertFalse($this->operationLocked());
    }

    public function test_expiring_an_old_operation_preserves_the_newer_operation_and_owner(): void
    {
        $this->requireProgress();
        $actor = $this->user();
        [$old, $oldUrl] = $this->queue($actor);
        Cache::restoreLock('laravelusers.packages', $old->lockOwner)->release();
        [$new] = $this->queue($actor);
        $this->makeStale($old);

        $this->getJson($oldUrl)->assertOk()->assertJsonPath('status', 'failed');

        $this->assertSame($new->id, PackageOperations::latest($actor)['id']);
        $this->assertSame($new->lockOwner, Cache::get('laravelusers.packages.owner'));
        $this->assertTrue($this->operationLocked());
    }

    public function test_another_actor_cannot_inspect_or_expire_a_queued_operation(): void
    {
        $this->requireProgress();
        [$job, $url] = $this->queue();
        $this->makeStale($job);

        $this->actingAs($this->user())->getJson($url)->assertNotFound();

        $this->assertSame('queued', Cache::get('laravelusers.package.'.$job->id)['status']);
        $this->assertSame($job->lockOwner, Cache::get('laravelusers.packages.owner'));
        $this->assertTrue($this->operationLocked());
    }

    public function test_configuration_requires_settings_and_package_authorization_before_queueing(): void
    {
        $this->installedPackage();
        $this->actingAs($this->user());
        $payload = ['package' => 'spatie', 'operation' => 'configure', 'confirmation' => 'continue', 'acknowledgement' => 1, 'migrate' => true];
        Gate::define('manage-laravelusers-packages', fn () => false);
        $this->postJson('/users/settings/packages', $payload)->assertForbidden();
        Gate::define('manage-laravelusers-packages', fn () => true);
        Gate::define('manage-laravelusers-settings', fn () => false);
        $this->postJson('/users/settings/packages', $payload)->assertForbidden();
        Gate::define('manage-laravelusers-settings', fn () => true);
        config(['laravelusers.middleware' => ['can:configure-host-packages']]);
        Gate::define('configure-host-packages', fn () => false);
        $this->postJson('/users/settings/packages', $payload)->assertForbidden();
        Bus::assertNothingDispatched();
        $this->assertNull(Cache::get('laravelusers.packages.owner'));
    }

    public function test_configuration_worker_rechecks_grouped_authorization_before_setup_or_migrations(): void
    {
        $this->installedPackage();
        $this->app->make('router')->middlewareGroup('host-package-admin', ['can:configure-host-packages']);
        config(['laravelusers.middleware' => ['host-package-admin']]);
        Gate::define('configure-host-packages', fn () => true);
        $response = $this->actingAs($this->user())->postJson('/users/settings/packages', ['package' => 'spatie', 'operation' => 'configure', 'confirmation' => 'continue', 'acknowledgement' => 1, 'setup' => false, 'migrate' => true])->assertStatus(202);
        $job = Bus::dispatched(ChangeManagedPackage::class)->last();
        $this->assertTrue(Cache::get('laravelusers.package.'.$job->id)['setup']);
        Gate::define('configure-host-packages', fn () => false);
        $composer = \Mockery::mock(ComposerPackages::class);
        $composer->shouldNotReceive('changeFromSettings', 'setup');

        $job->handle($this->packages, $composer, new UserSettings());

        $this->assertSame('failed', Cache::get('laravelusers.package.'.$job->id)['status']);
        $this->assertNull(Cache::get('laravelusers.packages.owner'));
        $this->assertFalse($this->operationLocked());
        $this->getJson($response->json('status_url'))->assertForbidden();
    }

    private function operationLocked(): bool
    {
        $contender = Cache::lock('laravelusers.packages', 10);
        if (!$contender->get()) {
            return true;
        }
        $contender->release();

        return false;
    }

    private function installedPackage(): void
    {
        $this->packages = \Mockery::mock(ManagedPackages::class)->makePartial();
        $this->packages->shouldReceive('installed')->with('spatie')->andReturnTrue();
        $this->app->instance(ManagedPackages::class, $this->packages);
    }

    private function makeStale(ChangeManagedPackage $job): void
    {
        config(['laravelusers.settings.packages.start_timeout' => 30]);
        $key = 'laravelusers.package.'.$job->id;
        Cache::put($key, array_replace(Cache::get($key), ['queued_at' => now()->subSeconds(31)->timestamp]), now()->addDay());
    }

    private function requireProgress(): void
    {
        if (!class_exists(PackageOperations::class)) {
            $this->markTestSkipped('This regression requires package operation progress tracking.');
        }
    }

    private function queue(?User $actor = null): array
    {
        $response = $this->actingAs($actor ?? $this->user())->postJson('/users/settings/packages', ['package' => 'spatie', 'operation' => 'install', 'confirmation' => 'continue', 'acknowledgement' => 1])->assertStatus(202);

        return [Bus::dispatched(ChangeManagedPackage::class)->last(), $response->json('status_url')];
    }
}
