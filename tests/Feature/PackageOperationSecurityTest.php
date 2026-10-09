<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use jeremykenedy\laravelusers\Jobs\ChangeManagedPackage;
use jeremykenedy\laravelusers\Support\ComposerPackages;
use jeremykenedy\laravelusers\Support\ManagedPackages;
use jeremykenedy\laravelusers\Support\UserSettings;
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
                'locked' => Cache::restoreLock('laravelusers.packages', $job->lockOwner)->isOwnedByCurrentProcess(),
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

    private function queue(): array
    {
        $response = $this->actingAs($this->user())->postJson('/users/settings/packages', ['package' => 'spatie', 'operation' => 'install', 'confirmation' => 'continue', 'acknowledgement' => 1])->assertStatus(202);

        return [Bus::dispatched(ChangeManagedPackage::class)->last(), $response->json('status_url')];
    }
}
